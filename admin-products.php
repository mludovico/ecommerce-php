<?php
use \mludovico\PageAdmin;
use \mludovico\Models\User;
use \mludovico\Models\Product;

$app->get('/admin/products', function(){
  User::verifyLogin();
  $search = (isset($_GET['search'])) ? $_GET['search'] : '';
  $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
  $pagination = Product::getProductsPerPage($page, $search);
  $pages = [];
  for($i = 1; $i <= $pagination["pages"]; $i++){
    array_push($pages, [
      'href'=>'/admin/products?' . http_build_query([
        'page'=>$i,
        'search'=>$search
      ]),
      'text'=>$i
    ]);
  }
  $page = new PageAdmin();
  $page->setTpl('products', array(
    'products'=>$pagination['data'],
    'search'=>$search,
    'pages'=>$pages,
  ));
});

$app->get('/admin/products/create', function(){
  User::verifyLogin();
  $page = new PageAdmin();
  $page->setTpl('products-create');
});

$app->post('/admin/products/create', function(){
  User::verifyLogin();
  $product = new Product();
  $product->setData($_POST);
  $product->save();
  header("Location: /admin/products");
  exit;
});

$app->get('/admin/products/:idprpduct', function($idproduct){
  User::verifyLogin();
  $product = new Product();
  $product->get((int)$idproduct);
  $page = new PageAdmin();
  $page->setTpl('products-update', array(
    "product"=>$product->getValues()
  ));
});

$app->post('/admin/products/:idproduct', function($idproduct){
  User::verifyLogin();
  $product = new Product();
  $product->get((int)$idproduct);
  $product->setData($_POST);
  $product->save();
  $product->setPhoto($_FILES["file"]);
  header("Location: /admin/products");
  exit;
});

$app->get('/admin/products/:idproduct/delete', function($idproduct){
  User::verifyLogin();
  $product = new Product();
  $product->get($idproduct);
  $product->delete();
  header("Location: /admin/products");
  exit;
});

?>