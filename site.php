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
  $cart = Cart::getFromSession();
  $address = new Address();
  $page = new Page();
  $page->setTpl('checkout', array(
    'cart'=>$cart->getValues(),
    'address'=>$address->getValues()
  ));
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

$app->get('/');

?>