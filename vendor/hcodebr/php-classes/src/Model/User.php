<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;

class User extends Model {

	const SESSION = "User";
	const SECRET = " HcodePhp7_Secret ";

	public static function login ($login, $password)
	{

		$sql = new Sql();

		$parametros = array(":LOGIN"=>$login);

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN",$parametros);

		if (count($results) === 0)
		{
			throw new \Exception ("Não há nenhum usuário ou senha inválida!");
		}

		$data = $results[0];

		if (password_verify($password, $data["despassword"]) === true)
		{
			$user = new User();

			$user->setData($data);

			
			$_SESSION[User::SESSION] = $user->getData();
			var_dump($_SESSION[User::SESSION]);
			//exit;
			return $user;
		}
		else{
			throw new \Exception("Não há nenhum usuário ou senha inválida!");			
		}

	}

	public static function verifyLogin($inadmin = true)
	{
		if (!isset($_SESSION[User::SESSION]) || !$_SESSION[User::SESSION] || !(int)$_SESSION[User::SESSION]["iduser"] > 0 || (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin)
		{
			header("Location: /admin/login");
			exit;
		}
	}

	public static function logout()
	{
		$_SESSION[User::SESSION] = NULL;
	}

	public static function listAll()
	{
		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

	}

	public function save()
	{

		$sql = new Sql();
		
		$parametros = array (
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		);

		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",$parametros);

		$this->setData($results[0]);
	}

	public function get($iduser)
	{
		$sql = new Sql();

		$parametros = array(":iduser"=>$iduser);

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING (idperson) WHERE a.iduser = :iduser",$parametros);

		$this->setData($results[0]);
	}

	public function update()
	{
		$sql = new Sql();
		
		$parametros = array (
			":iduser"=>$this->getiduser(),
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		);

		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",$parametros);

		$this->setData($results[0]);

	}

	public function delete()
	{
		$sql = new Sql();

		$parametros = array (
			":iduser"=>$this->getiduser()
		);

		$sql->select("CALL sp_users_delete(:iduser)",$parametros);		
	}

	public static function getForgot($email)
	{

		$sql = new Sql();
		$parametros = array(":email"=>$email);

		$results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email;", $parametros);

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
			
		} else{
			$data = $results[0];
			$parametros2 = array(":iduser"=>$data["iduser"],
								":desip"=>$_SERVER["REMOTE_ADDR"]);
			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",$parametros2);

			if (count($results2) === 0)
			{
				throw new \Exception("Não foi possível recuperar a senha.");
				
			}
			else{
				
				$dataRecovery = $results2[0];

				$idrecovery = $dataRecovery["idrecovery"];

				//$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $idrecovery, MCRYPT_MODE_ECB));
				$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5(User::SECRET), $idrecovery, MCRYPT_MODE_CBC, md5(md5(User::SECRET))));
				//$code = $idrecovery;
				$link = "http://www.celioecommerce.com.br/admin/forgot/reset?code=$code";

				$mailer = new Mailer($data["desemail"],$data["desperson"],"Redefinir Senha da Hcode Store", "forgot", array("name"=>$data["desperson"],
												"link"=>$link
											));
				$mailer->send();

				return $data;

			}
		}

	}

	public static function validForgotDecrypt($code)
	{
		//$idrecovery = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, User::SECRET, base64_decode($code), MCRYPT_MODE_ECB);	

		$idrecovery = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(User::SECRET), base64_decode($code), MCRYPT_MODE_CBC, md5(md5(User::SECRET))), '\0');	
		//$idrecovery = $code;
		$sql = new Sql();

		//$parametros3 = array(":idrecovery"=>$idrecovery);
		$parametros3 = array(":idrecovery"=>$idrecovery);
		//var_dump($idrecovery);

		$results = $sql->select("SELECT * from tb_userspasswordsrecoveries a 
		INNER JOIN tb_users b using (iduser)
		INNER JOIN tb_persons c using (idperson)
		WHERE a.idrecovery = :idrecovery
		AND a.dtrecovery IS NULL 
		AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW()",$parametros3);

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
			
		}
		else
		{
			return $results[0];

		}

	}

	public static function setForgottenUsed($idrecovery)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery",array(":idrecovery"=>$idrecovery));

	}

	public function setPassword($password)
	{
		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
			":password"=>$password,
			":iduser"=>$this->getiduser()
		));
	}

}

?>