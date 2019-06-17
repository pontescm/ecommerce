<?php

namespace Hcode;

class Model {

	private $values = [];

	public function __call($name, $args)
	{
		$method = substr($name,0,3); //para saber se o método é set ou get

		$fieldName = substr($name,3,strlen($name)); 

//		var_dump($method, $fieldName);
//		exit;
		switch ($method) {
			case "get":
				return $this->values[$fieldName];
			break;
			
			case "set":
				 $this->values[$fieldName] = $args[0];
			break;
		}
	}

	public function setData($data = array())
	{
		foreach ($data as $key => $value) 
		{
			$this->{"set".$key}($value);
		}
	}

	public function getData()
	{
		return $this->values;
	}

}

?>