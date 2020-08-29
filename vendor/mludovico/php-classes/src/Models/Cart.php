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

  public function addProduct(Product $product)
  {
    $sql = new Sql();
    $sql->query(
      "INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct);", array(
        ":idcart"=>$this->getidcart(),
        ":idproduct"=>$product->getidproduct()
      )
    );
  }

  public function removeProduct(Product $product, $all = false)
  {
    $sql = new Sql();
    if($all)
      $sql->query(
        "UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL;", array(
          ":idcart"=>$this->getidcart(),
          ":idproduct"=>$product->getidproduct()
        )
      );
    else
    $sql->query(
      "UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1;", array(
        ":idcart"=>$this->getidcart(),
        ":idproduct"=>$product->getidproduct()
      )
    );
  }

  public function getProducts()
  {
    $sql = new Sql();
    $results = $sql->select(
      "SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) as nrqtd, SUM(b.vlprice) as vltotal
       FROM tb_cartsproducts a
       INNER JOIN tb_products b
       ON a.idproduct = b.idproduct
       WHERE a.idcart = :idcart AND a.dtremoved is NULL
       GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl
       ORDER BY b.desproduct", array(
        "idcart"=>$this->getidcart()
      )
    );
    return Product::checklist($results);
  }

}
?>