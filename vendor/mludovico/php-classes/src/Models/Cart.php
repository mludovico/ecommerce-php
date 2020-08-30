<?php

namespace mludovico\Models;
use mludovico\DB\Sql;
use mludovico\Model;
use mludovico\Models\Product;
use mludovico\Models\User;

class Cart extends Model{

  const SESSION = 'Cart';
  const SESSION_ERROR = 'CartError';

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
    $this->getCalculateTotal();
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
      "UPDATE tb_cartsproducts
       SET dtremoved = NOW()
       WHERE idcartproduct = (
        SELECT idcartproduct
        FROM tb_cartsproducts
        WHERE idcart = :idcart
        AND idproduct = :idproduct 
        AND dtremoved IS NULL
        LIMIT 1);", array(
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

  public function getProductsTotals()
  {
    $sql = new Sql();
    $results = $sql->select(
      "SELECT 
      SUM(vlprice) AS vlprice,
      SUM(vlwidth) AS vlwidth,
      SUM(vlheight) AS vlheight,
      SUM(vllength) AS vllength,
      SUM(vlweight) AS vlweight,
      COUNT(*) as nrqtd
      FROM tb_products a
      INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
      WHERE b.idcart = :idcart AND dtremoved IS NULL;", array(
        'idcart'=>$this->getidcart()
      )
    );
    if(count($results) > 0)
      return $results[0];
    else
      return [];
  }

  public function setFreight($zipcode)
  {
    $zipcode = str_replace('-', '', $zipcode);
    $totals = $this->getProductsTotals();
    if($totals['vlheight'] < 2)
      $totals['vlheight'] = 2;
    if($totals['vllength'] < 16)
      $totals['vllength'] = 16;
    if($totals['nrqtd'] > 0){
      $qs = http_build_query([
        "nCdEmpresa"=>'',
        "sDsSenha"=>'',
        "nCdServico"=>'40010',
        "sCepOrigem"=>'13070091',
        "sCepDestino"=>$zipcode,
        "nVlPeso"=>$totals['vlweight'],
        "nCdFormato"=>'1',
        "nVlComprimento"=>$totals['vllength'],
        "nVlAltura"=>$totals['vlheight'],
        "nVlLargura"=>$totals['vlwidth'],
        "nVlDiametro"=>'0',
        "sCdMaoPropria"=>'S',
        "nVlValorDeclarado"=>$totals['vlprice'],
        "sCdAvisoRecebimento"=>'S'
      ]);
      $xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?" . $qs);
      $results = $xml->Servicos->cServico;

      if($results->MsgErro != ''){
        Cart::setMsgError((String)$results->MsgErro);
      }else{
        Cart::clearMsgError();
      }

      $this->setnrdays($results->PrazoEntrega);
      $this->setvlfreight(Cart::formatValueToDecimal($results->Valor));
      $this->setdeszipcode($zipcode);
      $this->save();
      return $results;
    }else{

    }
  }

  private static function formatValueToDecimal($value):float{
    $value = str_replace('.', '', $value);
    $value = str_replace(',', '.', $value);
    return $value;
  }

  public static function setMsgError($msg){
    $_SESSION[Cart::SESSION_ERROR] = $msg;
  }

  public static function getMsgError(){
    $msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";
    Cart::clearMsgError();
    return $msg;
  }

  public static function clearMsgError(){
    $_SESSION[Cart::SESSION_ERROR] = NULL;
  }

  public function updateFreight(){
    if($this->getdeszipcode() != '')
      $this->setFreight($this->getdeszipcode());
  }

  public function getValues()
  {
    $this->getCalculateTotal();
    return parent::getValues();
  }

  public function getCalculateTotal()
  {
    $this->updateFreight();
    $totals = $this->getProductsTotals();
    $this->setvlsubtotal($totals['vlprice']);
    $this->setvltotal($totals['vlprice'] + $this->getvlfreight());
  }

}
?>