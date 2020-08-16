<?php 

require_once("vendor/autoload.php");

$app = new \Slim\Slim();

$app->config('debug', true);

$app->get('/', function() {
    
  $sql = new mludovico\DB\Sql();
  $results = $sql->select("select * from tb_products");
  var_dump($results);

});

$app->run();

 ?>