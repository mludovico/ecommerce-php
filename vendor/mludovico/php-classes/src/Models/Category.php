<?php

namespace mludovico\Models;
use mludovico\DB\Sql;
use mludovico\Model;
use mludovico\Models\Product;

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
    Category::updateFile();
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
    Category::updateFile();
  }

  public function updateFile()
  {
    $categories = Category::listAll();
    $html = [];
    foreach ($categories as $row) {
      array_push($html, '<li><a href="/category/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>');
    }
    file_put_contents(
      $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'categories-menu.html',
      implode('', $html)
    );
  }

  public function getProducts($related = true)
  {
    $sql = new Sql();
    if($related)
    {
      return $sql->select(
        "SELECT * FROM tb_products WHERE idproduct IN(
          SELECT a.idproduct FROM tb_products a
          INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
          WHERE b.idcategory = :idcategory
        );", array(
          ":idcategory"=>$this->getidcategory()
        )
     );
    }else{
      return $sql->select(
        "SELECT * FROM tb_products WHERE idproduct NOT IN(
          SELECT a.idproduct FROM tb_products a
          INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
          WHERE b.idcategory = :idcategory
        );", array(
          ":idcategory"=>$this->getidcategory()
        )
     );
    }
  }

  public function getProductsPerPage($page = 1, $itemsPerPage = 3)
  {
    $sql = new Sql();
    $results = $sql->select(
      "SELECT *, COUNT(*) OVER() AS nrtotal
       FROM tb_products a
       INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
       INNER JOIN tb_categories c ON c.idcategory = b.idcategory
       WHERE c.idcategory = :idcategory
       LIMIT :itemsPerPage
       OFFSET :page", array(
        "idcategory"=>$this->getidcategory(),
        "itemsPerPage"=>$itemsPerPage,
        "page"=>($page-1) * $itemsPerPage
       )
    );
    $data = [
      "data"=>Product::checklist($results),
      "total"=>(int)$results[0]['nrtotal'],
      "pages"=>ceil($results[0]['nrtotal'] / $itemsPerPage)
    ];
    return($data);
  }

  public function addProduct(Product $product)
  {
    $sql = new Sql();
    $sql->query(
      "INSERT INTO tb_productscategories VALUES (:idcategory, :idproduct);", array(
        ":idcategory"=>$this->getidcategory(),
        ":idproduct"=>$product->getidproduct()
      )
    );
  }

  public function removeProduct(Product $product)
  {
    $sql = new Sql();
    $sql->query(
      "DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct;", array(
        ":idcategory"=>$this->getidcategory(),
        ":idproduct"=>$product->getidproduct()
      )
    );
  }

}
?>