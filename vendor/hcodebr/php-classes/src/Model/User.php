<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;

class User extends Model {

	const SESSION = "User";

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


}

?>