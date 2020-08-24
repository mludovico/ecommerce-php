<?php

use \mludovico\Page;
use \mludovico\Models\Category;

$app->get('/', function() {

  $page = new Page();
  $page->setTpl("index");
  
});

$app->get('/category/:idcategory', function($idcategory){
  $category = new Category();
  $category->get($idcategory);
  $page = new Page();
  $page->setTpl('category', array(
    "category"=>$category->getValues(),
    "products"=>[]
  ));
});
?>