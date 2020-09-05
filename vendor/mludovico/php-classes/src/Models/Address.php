<?php

namespace mludovico\Models;
use mludovico\DB\Sql;
use mludovico\Model;
use mludovico\Models\Product;
use mludovico\Models\User;

class Address extends Model{

  const ERROR = 'Error';

  public static function getCEP($nrcep)
  {
    $nrcep = str_replace("-", "", $nrcep);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://viacep.com.br/ws/$nrcep/json/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $data;
  }

  public function loadFromCep($nrcep)
  {
    $data = Address::getCep($nrcep);
    if(isset($data['logradouro']) && $data['logradouro']){
      $this->setdesaddress($data['logradouro']);
      $this->setdescomplement($data['complemento']);
      $this->setdesdistrict($data['bairro']);
      $this->setdescity($data['localidade']);
      $this->setdesstate($data['uf']);
      $this->setdescountry('Brasil');
      $this->setdeszipcode($nrcep);
    }
  }

  public function save(Type $var = null){
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * FROM sp_addresses_save(:idaddress, :idperson, :desaddress, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict)", array(
        ':idaddress'=>$this->getidaddress(),
        ':idperson'=>$this->getidperson(),
        ':desaddress'=>$this->getdesaddress(),
        ':descomplement'=>$this->getdescomplement(),
        ':descity'=>$this->getdescity(),
        ':desstate'=>$this->getdesstate(),
        ':descountry'=>$this->getdescountry(),
        ':deszipcode'=>$this->getdeszipcode(),
        ':desdistrict'=>$this->getdesdistrict()
      )
    );
    if(count($results) > 0)
      $this->setData($results[0]);
  }

  public static function setError($msg){
    $_SESSION[Address::ERROR] = $msg;
  }

  public static function getError(){
    $msg = (isset($_SESSION[Address::ERROR])) ? $_SESSION[Address::ERROR] : "";
    Address::clearError();
    return $msg;
  }

  public static function clearError(){
    $_SESSION[Address::ERROR] = NULL;
  }

}
?>