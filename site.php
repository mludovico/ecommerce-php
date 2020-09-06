<?php

use \mludovico\Page;
use \mludovico\Models\Category;
use \mludovico\Models\Product;
use \mludovico\Models\Cart;
use \mludovico\Models\Address;
use \mludovico\Models\User;
use \mludovico\Models\Order;
use \mludovico\Models\OrderStatus;

$app->get('/', function() {

  $products = Product::listAll();

  $page = new Page();
  $page->setTpl("index", array(
    "products"=>Product::checkList($products)
  ));
  
});

$app->get('/category/:idcategory', function($idcategory){
  $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
  $category = new Category();
  $category->get($idcategory);
  $pagination = $category->getProductsPerPage($page);
  $pages = [];
  for($i = 1; $i <= $pagination["pages"]; $i++){
    array_push($pages, [
      'link'=>'/category/' . $category->getidcategory() . '?page=' . $i,
      'page'=>$i
    ]);
  }
  $page = new Page();
  $page->setTpl('category', array(
    "category"=>$category->getValues(),
    "products"=>$pagination['data'],
    "pages"=>$pages
  ));
});

$app->get('/products/:desurl', function($desurl){
  $product = new Product();
  $product->getFromUrl($desurl);
  $page = new Page();
  $page->setTpl('product-detail', array(
    'product'=>$product->getValues(),
    'categories'=>$product->getCategories()
  ));
});

$app->get('/cart', function(){
  $cart = Cart::getFromSession();
  $page = new Page();
  $page->setTpl('cart', array(
    'cart'=>$cart->getValues(),
    'products'=>$cart->getProducts(),
    'msgError'=>Cart::getMsgError()
  ));
});

$app->get('/cart/:idproduct/add', function($idproduct){
  $product = new Product();
  $product->get((int)$idproduct);
  $cart = Cart::getFromSession();
  $qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;
  for($i = 0; $i < $qtd; $i++){
    $cart->addProduct($product);
  }
  $cart->getCalculateTotal();
  header("Location: /cart");
  exit;
});

$app->get('/cart/:idproduct/minus', function($idproduct){
  $product = new Product();
  $product->get((int)$idproduct);
  $cart = Cart::getFromSession();
  $cart->removeProduct($product);
  $cart->getCalculateTotal();
  header("Location: /cart");
  exit;
});

$app->get('/cart/:idproduct/remove', function($idproduct){
  $product = new Product();
  $product->get((int)$idproduct);
  $cart = Cart::getFromSession();
  $cart->removeProduct($product, true);
  $cart->getCalculateTotal();
  header("Location: /cart");
  exit;
});

$app->post('/cart/freight', function(){
  $cart = Cart::getFromSession();
  $cart->setFreight($_POST['zipcode']);
  header("Location: /cart");
  exit;
});

$app->get('/checkout', function(){
  User::verifyLogin(false);
  $address = new Address();
  $cart = Cart::getFromSession();
  if(isset($_GET['zipcode'])){
    $address->loadFromCep($_GET['zipcode']);
    $cart->setdeszipcode($_GET['zipcode']);
    $cart->save();
    $cart->getCalculateTotal();
  }
  if(!$address->getdeszipcode()) $address->setdeszipcode('');
  if(!$address->getdesaddress()) $address->setdesaddress('');
  if(!$address->getdescomplement()) $address->setdescomplement('');
  if(!$address->getdesdistrict()) $address->setdesdistrict('');
  if(!$address->getdescity()) $address->setdescity('');
  if(!$address->getdesstate()) $address->setdesstate('');
  if(!$address->getdescountry()) $address->setdescountry('');
  $page = new Page();
  $page->setTpl('checkout', array(
    'cart'=>$cart->getValues(),
    'address'=>$address->getValues(),
    'products'=>$cart->getProducts(),
    'error'=>Address::getError()
  ));
});

$app->post('/checkout', function(){
  User::verifyLogin(false);
  foreach (['zipcode'=>'CEP', 'desaddress'=>'Endereço', 'desdistrict'=>'Bairro', 'descity'=>'Cidade', 'desstate'=>'Estado', 'descountry'=>'País'] as $key=>$value) {
    if($key == 'zipcode' && !isset($_POST[$key]) || $_POST[$key] === '')
      $param = '?zipcode=' . $_POST['zipcode'];
    else
      $param = '';
    if(!isset($_POST[$key]) || $_POST[$key] === ''){
      Address::setError("Informe ". (substr($value, -1) == 'e' ? 'a ' : 'o ') . $value . '.');
      header("Location: /checkout$param");
      exit;
    }    
  }
  $user = User::getFromSession();
  $address = new Address();
  $_POST['idperson'] = $user->getidperson();
  $_POST['deszipcode'] = $_POST['zipcode'];
  $address->setData($_POST);
  $address->save();
  $cart = Cart::getFromSession();
  $cart->getCalculateTotal();
  $order = new Order();
  $order->setData([
    'idcart'=>$cart->getidcart(),
    'idaddress'=>$address->getidaddress(),
    'iduser'=>$user->getiduser(),
    'idstatus'=>OrderStatus::EM_ABERTO,
    'vltotal'=>$cart->getvltotal()
  ]);
  $order->save();
  header("Location: /order/" . $order->getidorder());
  exit;
});

$app->get('/login', function(){
  $page = new Page();
  $page->setTpl('login', array(
    'error'=>User::getLoginError(),
    'registerError'=>User::getRegisterError(),
    'registerValues'=>isset($_SESSION['registerValues'])
      ? 
        $_SESSION['registerValues']
      :
        ['name'=>'', 'email'=>'', 'phone'=>'']
  ));
});

$app->post('/login', function(){
  try{
    User::Login($_POST['login'], $_POST['password']);
  }catch(\Exception $e){
    User::setLoginError($e->getMessage());
  }
  header("Location: /checkout");
  exit;
});

$app->get('/logout', function(){
  User::logout();
  header("Location: /");
  exit;
});

$app->post('/register', function(){
  $_SESSION['registerValues'] = [
    'name'=>$_POST['name'],
    'email'=>$_POST['email'],
    'phone'=>$_POST['phone'],
  ];
  foreach (['name', 'email', 'password'] as $field) {
    if(!isset($_POST[$field]) || $_POST[$field] == ''){
      User::setRegisterError('Você deve inserir um valor para o ' . $field);
      header("Location: /login");
      exit;
    }
  }
  if(User::checkLoginExists($_POST['email']) === true){
    User::setRegisterError('Este endereço de email já está cadastrado.');
    header("Location: /login");
    exit;
  }
  $user = new User();
  $user->setData([
    'inadmin'=>0,
    'deslogin'=>$_POST['email'],
    'desperson'=>$_POST['name'],
    'desemail'=>$_POST['email'],
    'despassword'=>$_POST['password'],
    'nrphone'=>(int)$_POST['phone']
  ]);
  $user->save();
  User::login($_POST['email'], $_POST['password']);
  header("Location: /");
  exit;
});

$app->get('/forgot', function(){
  $page = new Page();
  $page->setTpl("forgot");
  });
  
  $app->post('/forgot', function(){
  $user = User::getForgot($_POST["email"], false);
  header("Location: /forgot/sent");
  exit;
});

$app->get('/forgot/sent', function(){
  
  $page = new Page();
  $page->setTpl("forgot-sent", array(
    "link"=>User::$link
  ));
});

$app->get('/forgot/reset', function(){
  
  $user = User::validForgotDecrypt($_GET['code']);
  $page = new Page();
  $page->setTpl("forgot-reset", array(
    "name"=>$user['desperson'],
    "code"=>$_GET['code']
  ));
});

$app->post('/forgot/reset', function(){
  
  $forgot = User::validForgotDecrypt($_POST['code']);
  User::setForgotUsed($forgot['idrecovery']);
  $user = new User();
  $user->get((int)$forgot['iduser']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT, [
    "cost"=>12
  ]);
  $user->setPassword($password);
  
  $page = new Page();
  $page->setTpl("forgot-reset-success");
});

$app->get('/profile', function(){
  User::verifyLogin(false);
  $user = User::getFromSession();
  $page = new Page();
  $page->setTpl('profile', array(
    'user'=>$user->getValues(),
    'profileMsg'=>User::getUserSuccess(),
    'profileError'=>User::getLoginError()
  ));
});

$app->post('/profile', function(){
  $user = User::getFromSession();
  User::verifyLogin(false);
  if(!isset($_POST['desperson']) || $_POST['desperson'] === ''){
    User::setLoginError("Preencha o seu nome.");
    header("Location: /profile");
    exit;
  }
  if(!isset($_POST['desemail']) || $_POST['desemail'] === ''){
    User::setLoginError("Preencha o seu email.");
    header("Location: /profile");
    exit;
  }
  if($_POST['desemail'] !== $user->getdesemail()){
    if(User::checkLoginExists($_POST['desemail'])){
      User::setLoginError("Este endereço de email já está em uso.");
      header("Location: /profile");
      exit;
    }
  }
  $_POST['inadmin'] = $user->getinadmin();
  $_POST['despassword'] = $user->getdespassword();
  $_POST['deslogin'] = $_POST['desemail'];
  $user->setData($_POST);
  $user->update();
  User::setUserSuccess("Dados alterados com sucesso");
  header("Location: /profile");
  exit;
});

$app->get('/order/:idorder', function($idorder){
  User::verifyLogin(false);
  $order = new Order();
  $order->get((int)$idorder);
  $page = new Page();
  $page->setTpl('payment', array(
    'order'=>$order->getValues(),
  ));
});

$app->get('/payment/:idorder', function($idorder){
  User::verifyLogin(false);
  $order = new Order();
  $order->get((int)$idorder);
  // DADOS DO BOLETO PARA O SEU CLIENTE
  $dias_de_prazo_para_pagamento = 10;
  $taxa_boleto = 5.00;
  $data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
  $valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
  $valor_cobrado = str_replace(",", ".",$valor_cobrado);
  $valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

  $dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
  $dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
  $dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
  $dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
  $dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
  $dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

  // DADOS DO SEU CLIENTE
  $dadosboleto["sacado"] = $order->getdesperson();
  $dadosboleto["endereco1"] = $order->getdesaddres() . " " . $order->getdesdistrict();
  $dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdestate() . " -  CEP: " . $order->getdeszipcode();

  // INFORMACOES PARA O CLIENTE
  $dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Mludovico Store";
  $dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
  $dadosboleto["demonstrativo3"] = "";
  $dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
  $dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
  $dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: maludovico@gmail.com";
  $dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Mludovico E-commerce - www.marcelo-ludovico.web.app";

  // DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
  $dadosboleto["quantidade"] = "";
  $dadosboleto["valor_unitario"] = "";
  $dadosboleto["aceite"] = "";		
  $dadosboleto["especie"] = "R$";
  $dadosboleto["especie_doc"] = "";


  // ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


  // DADOS DA SUA CONTA - ITAÚ
  $dadosboleto["agencia"] = "1341"; // Num da agencia, sem digito
  $dadosboleto["conta"] = "01371";	// Num da conta, sem digito
  $dadosboleto["conta_dv"] = "0"; 	// Digito do Num da conta

  // DADOS PERSONALIZADOS - ITAÚ
  $dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

  // SEUS DADOS
  $dadosboleto["identificacao"] = "Mludovico Sistemas";
  $dadosboleto["cpf_cnpj"] = "000.000.000-00";
  $dadosboleto["endereco"] = "Avenida Papa Pio XII, 63 - Jd Chapadão, 13070-091";
  $dadosboleto["cidade_uf"] = "Campinas - SP";
  $dadosboleto["cedente"] = "Mludovico Sistemas";

  // NÃO ALTERAR!
  $path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;
  require_once($path . "funcoes_itau.php"); 
  require_once($path . "layout_itau.php");
});

$app->get('/profile/orders', function(){
  User::verifyLogin(false);
  $user = User::getFromSession();
  $page = new Page();
  $page->setTpl('profile-orders', array(
    'orders'=>$user->getOrders(),
  ));
});

$app->get('/profile/orders/:idorder', function($idorder){
  User::verifyLogin(false);
  $order = new Order();
  $order->get((int)$idorder);
  $cart = new Cart();
  $cart->get((int)$order->getidcart());
  $cart->getCalculateTotal();
  $page = new Page();
  $page->setTpl('profile-orders-detail', array(
    'order'=>$order->getValues(),
    'products'=>$cart->getProducts(),
    'cart'=>$cart->getValues()
  ));
});

$app->get('/profile/change-password', function(){
  User::verifyLogin(false);
  $page = new Page();
  $user = User::getFromSession();

  $page->setTpl('profile-change-password', array(
    'changePassError'=>$user->getPassError(),
    'changePassSuccess'=>$user->getPassSuccess()
  ));
});

$app->post('/profile/change-password', function(){
  User::verifyLogin(false);
  if(!isset($_POST['current_pass']) || strlen($_POST['current_pass']) === 0){
    User::setPassError('Digite sua senha atual.');
    header("Location: /profile/change-password");
    exit;
  }
  if(!isset($_POST['new_pass']) || strlen($_POST['new_pass']) === 0){
    User::setPassError('Digite a nova senha.');
    header("Location: /profile/change-password");
    exit;
  }
  if(!isset($_POST['new_pass_confirm']) || strlen($_POST['new_pass_confirm']) === 0){
    User::setPassError('Confirme a nova senha.');
    header("Location: /profile/change-password");
    exit;
  }
  if($_POST['current_pass'] === $_POST['new_pass']){
    User::setPassError('A nova senha deve ser diferente da atual.');
    header("Location: /profile/change-password");
    exit;
  }
  if($_POST['new_pass'] !== $_POST['new_pass_confirm']){
    User::setPassError('O campo confirmação não confere com a nova senha digitada.');
    header("Location: /profile/change-password");
    exit;
  }
  $user = User::getFromSession();
  if(!password_verify($_POST['current_pass'], $user->getdespassword())){
    User::setPassError('Senha atual inválida.');
    header("Location: /profile/change-password");
    exit;
  }
  $user->setdespassword($_POST['new_pass']);
  $user->update();
  User::setPassSuccess('Senha alterada com sucesso!');
  header("Location: /profile/change-password");
  exit;
});

$app->get('/test', function(){
  
});
  
$app->get('/');

?>