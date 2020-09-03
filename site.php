<?php

use \mludovico\Page;
use \mludovico\Models\Category;
use \mludovico\Models\Product;
use \mludovico\Models\Cart;
use \mludovico\Models\Address;
use \mludovico\Models\User;

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
  header("Location: /order");
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

$app->get('/test', function(){
  $array = ['key'=>'value'];
  echo "Test interpolating assoc array values $array=>key";
  exit;
});
  
$app->get('/');

?>