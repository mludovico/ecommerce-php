<?php

use \mludovico\Page;
use \mludovico\Models\Category;
use \mludovico\Models\Product;

$app->get('/', function() {

  $products = Product::listAll();

  $page = new Page();
  $page->setTpl("index", array(
    "products"=>Product::checkList($products)
  ));
  
});

$app->get('/category/:idcategory', function($idcategory){
  $category = new Category();
  $category->get($idcategory);
  $page = new Page();
  $page->setTpl('category', array(
    "category"=>$category->getValues(),
    "products"=>Product::checklist($category->getProducts())
  ));
});

$app->get('/');

?>