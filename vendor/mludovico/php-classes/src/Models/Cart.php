<?php

namespace mludovico\Models;
use mludovico\DB\Sql;
use mludovico\Model;
use mludovico\Models\Product;
use mludovico\Models\User;

class Cart extends Model{

  const SESSION = 'Cart';

  public static function getFromSession(){
    $cart = new Cart();
      if(isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0){
        $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
      }else{
        $cart->getFromSessionId();
        if(!(int)$cart->getidcart() > 0){
          $data = [
            'dessessionid'=>session_id(),
          ];
          if(User::checklogin(false)){
            $user = User::getFromSession();
            $data['iduser'] = $user->getiduser();
          }
          $cart->setData($data);
          $cart->save();
          $cart->setToSession();
        }
      }
      return $cart;
  }

  public function setToSession()
  {
    $_SESSION[Cart::SESSION] = $this->getValues();
  }

  public function get($idcart)
  {
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * FROM tb_cart WHERE idcart = :idcart", array(
        "idcart"=>$idcart
      )
    );
    if(count($results) > 0)
      $this->setData($results[0]);
  }

  public function getFromSessionId()
  {
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * FROM tb_cart WHERE dessessionid = :dessessionid", array(
        "dessessionid"=>session_id()
      )
    );
    if(count($results) > 0)
      $this->setData($results[0]);
  }

  public function delete(){
    $sql = new Sql();
    $sql->select(
      "DELETE FROM tb_categories WHERE idcategory = :idcategory", array(
        ":idcategory"=>$this->getidcategory()
      )
    );
    Category::updateFile();
  }

  public function save(){
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * FROM sp_cart_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", array(
        ":idcart"=>$this->getidcart(),
        ":dessessionid"=>$this->getdessessionid(),
        ":iduser"=>$this->getiduser(),
        ":deszipcode"=>$this->getdeszipcode(),
        ":vlfreight"=>$this->getvlfreight(),
        ":nrdays"=>$this->getnrdays(),
      )
    );
    if(count($results) === 0)
      throw new \Exception("Falha ao cadastrar carrinho");
    $this->setData($results[0]);
  }

}
?>