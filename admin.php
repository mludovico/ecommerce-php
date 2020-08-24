<?php

use \mludovico\PageAdmin;
use \mludovico\Models\User;
require_once('admin-users.php');
require_once('admin-passwords.php');
require_once('admin-categories.php');
require_once('admin-products.php');

$app->get('/admin', function() {

User::verifyLogin();
$page = new PageAdmin();
$page->setTpl("index");

});

$app->get('/admin/login', function() {

$page = new PageAdmin([
  "header"=>false,
  "footer"=>false
]);
$page->setTpl("login");

});

$app->get('/admin/logout', function(){
User::logout();
header("Location: /admin/login");
exit;
});

$app->post('/admin/login', function() {

User::login($_POST["deslogin"], $_POST["despassword"]);

header("Location: /admin");
exit;

});
?>