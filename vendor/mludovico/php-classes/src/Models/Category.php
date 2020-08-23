<?php

namespace mludovico\Models;
use mludovico\DB\Sql;
use mludovico\Model;

class Category extends Model{

  public static function listAll(){
    $sql = new Sql();

    return $sql->select("SELECT * FROM tb_categories");
  }

  public function save(){
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * FROM sp_categories_save(:pidcategory, :descategory);", array(
        ":pidcategory"=>$this->getidcategory(),
        ":descategory"=>$this->getdescategory()
      )
    );
    if(count($results) === 0)
      throw new \Exception("Falha ao cadastrar categoria");
    $this->setData($results[0]);
  }

  public function get($idcategory)
  {
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * FROM tb_categories WHERE idcategory = :idcategory", array(
        "idcategory"=>$idcategory
      )
    );
    if(count($results) === 0)
      throw new \Exception("Categoria não encontrado");
    $this->setData($results[0]);
  }

  public function delete(){
    $sql = new Sql();
    $sql->select(
      "DELETE FROM tb_categories WHERE idcategory = :idcategory", array(
        ":idcategory"=>$this->getidcategory()
      )
    );
  }
}
?>