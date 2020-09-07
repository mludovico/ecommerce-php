<?php

namespace mludovico\Models;
use mludovico\DB\Sql;
use mludovico\Model;

class Product extends Model{

  public static function listAll(){
    $sql = new Sql();

    return $sql->select("SELECT * FROM tb_products");
  }

  public static function getProductsPerPage($page = 1, $search = '', $itemsPerPage = 10)
  {
    $sql = new Sql();
    $results = $sql->select(
      "SELECT *, COUNT(*) OVER() AS nrtotal
       FROM tb_products
       WHERE desproduct ILIKE :search
       ORDER BY desproduct
       LIMIT :itemsPerPage
       OFFSET :page", array(
        ":search"=>"%$search%",
        ":itemsPerPage"=>$itemsPerPage,
        ":page"=>($page-1) * $itemsPerPage
       )
    );
    $data = [
      "data"=>(count($results) > 0) ? $results : [],
      "total"=>(count($results) > 0) ? (int)$results[0]['nrtotal'] : 0,
      "pages"=>(count($results) > 0) ? ceil($results[0]['nrtotal'] / $itemsPerPage) : 1
    ];
    return($data);
  }

  public static function checkList($list)
  {
    foreach ($list as &$row) {
      $p = new Product();
      $p->setData($row);
      $row = $p->getValues();
    }
    return $list;
  }

  public function save(){
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * FROM sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl);", array(
        ":idproduct"=>$this->getidproduct(),
        ":desproduct"=>$this->getdesproduct(),
        ":vlprice"=>$this->getvlprice(),
        ":vlwidth"=>$this->getvlwidth(),
        ":vlheight"=>$this->getvlheight(),
        ":vllength"=>$this->getvllength(),
        ":vlweight"=>$this->getvlweight(),
        ":desurl"=>$this->getdesurl(),
      )
    );
    if(count($results) === 0)
      throw new \Exception("Falha ao cadastrar produto");
    $this->setData($results[0]);
    Product::updateFile();
  }

  public function get($idproduct)
  {
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * FROM tb_products WHERE idproduct = :idproduct", array(
        "idproduct"=>$idproduct
      )
    );
    if(count($results) === 0)
      throw new \Exception("Produto não encontrado");
    $this->setData($results[0]);
  }

  public function delete(){
    $sql = new Sql();
    $sql->select(
      "DELETE FROM tb_products WHERE idproduct = :idproduct", array(
        ":idproduct"=>$this->getidproduct()
      )
    );
    Product::updateFile();
  }

  public function checkPhoto()
  {
    if(file_exists(
      $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
      "res". DIRECTORY_SEPARATOR .
      "site". DIRECTORY_SEPARATOR .
      "img". DIRECTORY_SEPARATOR .
      "product-" . $this->getidproduct() . ".jpg"
    )){
      $url = "/res/site/img/product-" . $this->getidproduct() . ".jpg";
    }else{
      $url = "/res/site/img/crossword.png";
    }
    return $this->setdesphoto($url);    
  }

  public function getValues(){
    $this->checkPhoto();
    $values = parent::getValues();
    return $values;
  }

  public function setPhoto($file)
  {
    $extension = explode('.', $file['name']);
    $extension = end($extension);
    switch ($extension) {
      case 'jpg':
        $image = imagecreatefromjpeg($file["tmp_name"]);
        break;
      case 'jpeg':
        $image = imagecreatefromjpeg($file["tmp_name"]);
        break;
      case 'gif':
        $image = imagecreatefromgif($file["tmp_name"]);
        break;
      case 'png':
        $image = imagecreatefrompng($file["tmp_name"]);
        break;
    }
    $dest = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
    "res". DIRECTORY_SEPARATOR .
    "site". DIRECTORY_SEPARATOR .
    "img". DIRECTORY_SEPARATOR .
    "product-" . $this->getidproduct() . ".jpg";
    imagejpeg($image, $dest);
    imagedestroy($image);
    $this->checkPhoto();
  }

  public function updateFile()
  {
    $products = Product::listAll();
    $html = [];
    foreach ($products as $row) {
      array_push($html, '<li><a href="/product/'.$row['idproduct'].'">'.$row['desproduct'].'</a></li>');
    }
    file_put_contents(
      $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'products-menu.html',
      implode('', $html)
    );
  }

  public function getFromUrl($desurl){
    $sql = new Sql();
    $rows = $sql->select(
      "SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", array(
        ':desurl'=>$desurl
      )
    );
    $this->setData($rows[0]);
  }

  public function getCategories(){
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * FROM tb_categories a
       INNER JOIN tb_productscategories b
       ON a.idcategory = b.idcategory
       WHERE b.idproduct = :idproduct", array(
        ':idproduct'=>$this->getidproduct()
      )
    );
    return $results;
  }
}
?>