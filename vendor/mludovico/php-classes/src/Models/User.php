<?php

namespace mludovico\Models;
use mludovico\DB\Sql;
use mludovico\Model;
use mludovico\Mailer;

class User extends Model{

  const SESSION = "User";
  const SECRET = "CursoMLudovicoPHP7";
  const SECRET_IV = "CursoMLudovicoPHP7_IV";
  const ERROR = "UserError";
  const REGISTER_ERROR = "UserRegisterError";
  const SUCCESS = "UserSuccess";

  public static $link = "";

  public static function getFromSession(){
    $user = new User();
    if(isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0){
      $user->setData($_SESSION[User::SESSION]);
    }
    return $user;
  }
  
  public static function checkLogin($inadmin = true)
  {
    if(
      !isset($_SESSION[User::SESSION])
      ||
      !$_SESSION[User::SESSION]
      ||
      !(int)$_SESSION[User::SESSION]["iduser"] > 0
    ){
      return false;
    }else{
      if($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true){
       return true;
      }else if($inadmin === false){
        return true;
      }else{
        return false;
      }
    }
}

  public static function login($login, $password){
    $sql = new Sql();

    $results = $sql->select(
      "SELECT * FROM tb_users a
       INNER JOIN tb_persons b
       ON a.idperson = b.idperson
       WHERE deslogin = :login", array(
      ":login"=>$login
    ));

    if(count($results) === 0){
      throw new \Exception('Usuário inválido');
    }

    $data = $results[0];

    if (password_verify($password, $data["despassword"]) === true){
      $user = new User();
      $data['desperon'] = utf8_encode($data['desperson']);
      $user->setData($data);
      $_SESSION[User::SESSION] = $user->getValues();
      return $user;
    }else{
      throw new \Exception('Credenciais Inválidas');
    }
  }

  public static function verifyLogin($inadmin = true){
    if(!User::checkLogin($inadmin)){
      if($inadmin){
        header("Location: /admin/login");
      }else{
        header("Location: /login");
      }
      exit;
    }
  }

  public static function logout(){
    $_SESSION[User::SESSION] = null;
  }

  public static function listAll(){
    $sql = new Sql();

    return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.idperson");
  }

  public function save(){
    $sql = new Sql();
    $results = $sql->select("SELECT sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
      ":desperson"=>$this->getdesperson(),
      ":deslogin"=>$this->getdeslogin(),
      ":despassword"=>password_hash($this->getdespassword(), PASSWORD_DEFAULT),
      ":desemail"=>$this->getdesemail(),
      ":nrphone"=>$this->getnrphone(),
      ":inadmin"=>$this->getinadmin(),
    ));
    $this->setData($results[0]);
  }

  public function get($iduser){
    $sql = new Sql();
    $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
      ":iduser"=>$iduser
    ));
    $results[0]['desperson'] = utf8_decode($results[0]['desperson']);
    $this->setData($results[0]);
  }

  public function update(){
    $sql = new Sql();
    $results = $sql->select("SELECT sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
      ":iduser"=>$this->getiduser(),
      ":desperson"=>$this->getdesperson(),
      ":deslogin"=>$this->getdeslogin(),
      ":despassword"=>$this->getdespassword(),
      ":desemail"=>$this->getdesemail(),
      ":nrphone"=>$this->getnrphone(),
      ":inadmin"=>$this->getinadmin(),
    ));
    $this->setData($results[0]);
  }

  public function delete(){
    $sql = new Sql();
    $sql->select("SELECT sp_users_delete(:iduser)", array(
      ":iduser"=>$this->getiduser()
    ));
  }

  public static function getForgot($email, $inadmin = true){
    $sql = new Sql();
    $results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE desemail = :email", array(
      ":email"=>$email
    ));
    if(count($results) === 0)
    {
      throw new \Exception("Não foi possível recuperar a senha");
    }
    else
    {
      $data = $results[0];
      $results2 = $sql->select("SELECT * FROM sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
        ":iduser"=>$data["iduser"],
        ":desip"=>$_SERVER["REMOTE_ADDR"]
      ));
      if(count($results2) === 0)
      {
        throw new \Exception("Não foi possível recuperar a senha");
      }
      else
      {
        $dataRecovery = $results2[0];
        $code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));
        $code = base64_encode($code);
        if($inadmin === true)
          $link = "http://localhost:8080/admin/forgot/reset?code=$code";
        else
          $link = "http://localhost:8080/forgot/reset?code=$code";
        file_put_contents("link.txt", $link);
        $mailer = new Mailer($data['desemail'], $data['desperson'], "Redefenir senha", "forgot", array(
          "name"=>$data['desperson'],
          "link"=>$link
        ));
        return $mailer->send();
      }
    }
  }

  public static function validForgotDecrypt($code){
    $decoded = base64_decode($code);
    $idRecovery = openssl_decrypt($decoded, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));
    $sql = new Sql();
    $results = $sql->select(
      "SELECT * FROM tb_userspasswordsrecoveries a
      INNER JOIN tb_users b ON a.iduser = b.iduser
      INNER JOIN tb_persons c ON b.idperson = c.idperson
      WHERE a.idrecovery = :idrecovery
      AND a.dtrecovery is NULL
      AND a.dtregister +  '1 HOUR'::INTERVAL >= NOW();", array(
        ":idrecovery"=>$idRecovery
      )
    );
    if(count($results) === 0){
      throw new \Exception("Erro ao validar código de recuperação");
    }
    else{
      return $results[0];
    }
  }

  public static function setForgotUsed($idrecovery){
    $sql = new Sql();
    $sql->query(
      "UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery",
      array(
        ":idrecovery"=>$idrecovery
      )
    );
  }

  public function setPassword($password){
    $sql = new Sql();
    $sql->query(
      "UPDATE tb_users SET despassword = :password WHERE iduser = :iduser",
      array(
        ":password"=>$password,
        ":iduser"=>$this->getiduser()
      )
    );
  }

  public static function setLoginError($msg){
    $_SESSION[User::ERROR] = $msg;
  }

  public static function getLoginError(){
    $msg = (isset($_SESSION[User::ERROR])) ? $_SESSION[User::ERROR] : "";
    User::clearLoginError();
    return $msg;
  }

  public static function clearLoginError(){
    $_SESSION[User::ERROR] = NULL;
  }

  public static function setRegisterError($msg){
    $_SESSION[User::REGISTER_ERROR] = $msg;
  }

  public static function getRegisterError(){
    $msg = (isset($_SESSION[User::REGISTER_ERROR])) ? $_SESSION[User::REGISTER_ERROR] : "";
    User::clearRegisterError();
    return $msg;
  }

  public static function clearRegisterError(){
    $_SESSION[User::REGISTER_ERROR] = NULL;
  }

  public static function setUserSuccess($msg){
    $_SESSION[User::SUCCESS] = $msg;
  }

  public static function getUserSuccess(){
    $msg = (isset($_SESSION[User::SUCCESS])) ? $_SESSION[User::SUCCESS] : "";
    User::clearUserSuccess();
    return $msg;
  }

  public static function clearUserSuccess(){
    $_SESSION[User::SUCCESS] = NULL;
  }

  public static function checkLoginExists($login)
  {
    $sql = new Sql();
    $results = $sql->select(
      "SELECT deslogin FROM tb_users WHERE deslogin = :deslogin", array(
        ':deslogin'=>$login
      )
    );
    return (count($results) > 0);
  }

}

?>