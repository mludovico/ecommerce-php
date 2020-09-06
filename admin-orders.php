<?php

use \mludovico\PageAdmin;
use \mludovico\Models\User;
use \mludovico\Models\Order;
use \mludovico\Models\OrderStatus;
use \mludovico\Models\Cart;

$app->get('/admin/orders/:idorder/delete', function($idorder){
  User::verifyLogin();
  $order = new Order();
  $order->get((int)$idorder);
  $order->delete();
  header("Location: /admin/orders");
  exit;
});

$app->get('/admin/orders/:idorder/status', function($idorder){
  User::verifyLogin();
  $order = new Order();
  $order->get((int)$idorder);
  $page = new PageAdmin();
  $page->setTpl('order-status', array(
    'order'=>$order->getValues(),
    'status'=>OrderStatus::listAll(),
    'msgSuccess'=>Order::getOrderSuccess(),
    'msgError'=>Order::getOrderError(),
  ));
});
  
$app->post('/admin/orders/:idorder/status', function($idorder){
  User::verifyLogin();
  $order = new Order();
  $order->get((int)$idorder);
  if(!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0){
    Order::setOrderError('Informe o status atual');
    header("Location: /admin/orders/$idorder/status");
    exit;
  }
  $order->setidstatus((int)$_POST['idstatus']);
  $order->save();
  Order::setOrderSuccess('Status alterado com sucesso!');
  header("Location: /admin/orders/$idorder/status");
  exit;
});
  
$app->get('/admin/orders/:idorder', function($idorder){
  User::verifyLogin();
  $order = new Order();
  $order->get((int)$idorder);
  $cart = new Cart();
  $cart->get($order->getidcart());
  $page = new PageAdmin();
  $page->setTpl('order', array(
    'order'=>$order->getValues(),
    'cart'=>$cart->getValues(),
    'products'=>$cart->getProducts()
  ));
});
  
$app->get('/admin/orders', function(){
  User::verifyLogin();
  $page = new PageAdmin();
  $page->setTpl('orders', array(
    'orders'=>Order::getOrders()
  ));
});
  
  ?>