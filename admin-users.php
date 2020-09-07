<?php

use \mludovico\Models\User;
use \mludovico\PageAdmin;

$app->get('/admin/users/:iduser/password', function($iduser){
  User::verifyLogin();
  $user = new User();
  $user->get((int)$iduser);
  $page = new PageAdmin();
  $page->setTpl('users-password', array(
    'user'=>$user->getValues(),
    'msgSuccess'=>User::getPassSuccess(),
    'msgError'=>User::getPassError()
  ));
});

$app->post('/admin/users/:iduser/password', function($iduser){
  User::verifyLogin();
  if(!isset($_POST['despassword']) || strlen($_POST['despassword']) === 0){
    User::setPassError('Digite uma senha.');
    header("Location: /admin/users/$iduser/password");
    exit;
  }
  if(!isset($_POST['despassword-confirm']) || strlen($_POST['despassword-confirm']) === 0){
    User::setPassError('Confirme a senha.');
    header("Location: /admin/users/$iduser/password");
    exit;
  }
  if($_POST['despassword'] !== $_POST['despassword-confirm']){
    User::setPassError('O campo confirmação não confere com a senha digitada.');
    header("Location: /admin/users/$iduser/password");
    exit;
  }
  $user = new User();
  $user->get((int)$iduser);
  $user->setPassword($_POST['despassword']);
  User::setPassSuccess("Senha alterada com sucesso!");
  header("Location: /admin/users/$iduser/password");
  exit;
});

$app->get('/admin/users/:iduser/delete', function($iduser){

  User::verifyLogin();
  $user = new User();
  $user->get((int)$iduser);
  $user->delete();
  
  header("Location: /admin/users");
  exit;
  
});
  
$app->get('/admin/users', function(){
  
  User::verifyLogin();
  $search = (isset($_GET['search'])) ? $_GET['search'] : '';
  $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
  $pagination = User::getUsersPerPage($page, $search);
  $pages = [];
  for($i = 1; $i <= $pagination["pages"]; $i++){
    array_push($pages, [
      'href'=>'/admin/users?' . http_build_query([
        'page'=>$i,
        'search'=>$search
      ]),
      'text'=>$i
    ]);
  }
  $page = new PageAdmin();
  $page->setTpl("users", array(
    'users'=>$pagination['data'],
    'search'=>$search,
    'pages'=>$pages
  ));
});
  
$app->get('/admin/users/create', function(){
  
  User::verifyLogin();
  $page = new PageAdmin();
  $page->setTpl("users-create");
});
  
$app->get('/admin/users/:iduser', function($iduser){
  
  User::verifyLogin();
  $user = new User();
  $user->get((int)$iduser);
  $page = new PageAdmin();
  $page->setTpl("users-update", array(
    "user"=>$user->getValues()
  ));
});  
$app->post('/admin/users/create', function(){

  User::verifyLogin();
  $user = new User();
  $_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
  $user->setData($_POST);
  $user->save();
  header("Location: /admin/users");
  exit;
  
});
  
$app->post('/admin/users/:iduser', function($iduser){
  
  User::verifyLogin();
  $user = new User();
  $_POST['inadmin'] = (isset($_POST['inadmin']))?1:0;
  $user->get((int)$iduser);
  $user->setData($_POST);
  $user->update();
  
  header("Location: /admin/users");
  exit;
});
?>