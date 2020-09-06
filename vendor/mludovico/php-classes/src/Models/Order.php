<?php

namespace mludovico\Models;
use mludovico\DB\Sql;
use mludovico\Model;

class Order extends Model{

  const ORDER_SUCCESS = 'OrderSuccess';
  const ORDER_ERROR = 'OrderError';

  public function save()
  {
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * FROM sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal);", array(
        ':idorder'=>$this->getidorder(),
        ':idcart'=>$this->getidcart(),
        ':iduser'=>$this->getiduser(),
        ':idstatus'=>$this->getidstatus(),
        ':idaddress'=>$this->getidaddress(),
        ':vltotal'=>$this->getvltotal()
      )
    );
    $results = $sql->select(
      "SELECT * 
      FROM tb_orders a
      INNER JOIN tb_ordersstatus b USING(idstatus)
      INNER JOIN tb_cart c USING(idcart)
      INNER JOIN tb_users d ON d.iduser = a.iduser
      INNER JOIN tb_addresses e USING(idaddress)
      INNER JOIN tb_persons f ON f.idperson = d.idperson
      WHERE idorder = :idorder", array(
        ":idorder"=>$results[0]['idorder']
      )
    );
    if(count($results) > 0)
      $this->setData($results[0]);
  }

  public function get($idorder)
  {
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * 
      FROM tb_orders a
      INNER JOIN tb_ordersstatus b USING(idstatus)
      INNER JOIN tb_cart c USING(idcart)
      INNER JOIN tb_users d ON d.iduser = a.iduser
      INNER JOIN tb_addresses e USING(idaddress)
      INNER JOIN tb_persons f ON f.idperson = d.idperson
      WHERE idorder = :idorder", array(
        ":idorder"=>$idorder
      )
    );
    if(count($results) > 0)
      $this->setData($results[0]);
  }

  public static function getOrders()
  {
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * 
      FROM tb_orders a
      INNER JOIN tb_ordersstatus b USING(idstatus)
      INNER JOIN tb_cart c USING(idcart)
      INNER JOIN tb_users d ON d.iduser = a.iduser
      INNER JOIN tb_addresses e USING(idaddress)
      INNER JOIN tb_persons f ON f.idperson = d.idperson
      ORDER BY a.dtregister DESC"
    );
    return $results;
  }

  public function delete()
  {
    $sql = new Sql();
    $sql->query(
      "DELETE FROM tb_orders WHERE idorder = :idorder", array(
        ':idorder'=>$this->getidorder()
      )
    );
  }

  public static function setOrderSuccess($msg){
    $_SESSION[Order::ORDER_SUCCESS] = $msg;
  }

  public static function getOrderSuccess(){
    $msg = (isset($_SESSION[Order::ORDER_SUCCESS])) ? $_SESSION[Order::ORDER_SUCCESS] : "";
    Order::clearOrderSuccess();
    return $msg;
  }

  public static function clearOrderSuccess(){
    $_SESSION[Order::ORDER_SUCCESS] = NULL;
  }

  public static function setOrderError($msg){
    $_SESSION[Order::ORDER_ERROR] = $msg;
  }

  public static function getOrderError(){
    $msg = (isset($_SESSION[Order::ORDER_ERROR])) ? $_SESSION[Order::ORDER_ERROR] : "";
    Order::clearOrderError();
    return $msg;
  }

  public static function clearOrderError(){
    $_SESSION[Order::ORDER_ERROR] = NULL;
  }

}
?>