<?php 


namespace mludovico\DB;
require_once('secrets.php');

class Sql {
  
  private $conn;

	public function __construct()
	{
    $this->conn = new \PDO(
      'pgsql:host='.HOSTNAME.' user='.USERNAME.' dbname='.DBNAME.' password='.PASSWORD
    );
    $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
	}

	private function setParams($statement, $parameters = array())
	{

		foreach ($parameters as $key => $value) {
			
			$this->bindParam($statement, $key, $value);

		}

	}

	private function bindParam($statement, $key, $value)
	{

		$statement->bindParam($key, $value);

	}

	public function query($rawQuery, $params = array())
	{

		$stmt = $this->conn->prepare($rawQuery);

		$this->setParams($stmt, $params);

		$stmt->execute();

	}

	public function select($rawQuery, $params = array())
	{

		try {
      $stmt = $this->conn->prepare($rawQuery);

      $this->setParams($stmt, $params);

      $stmt->execute();
      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }catch(Exception $e){
      die($e);
    }


	}

}

 ?>