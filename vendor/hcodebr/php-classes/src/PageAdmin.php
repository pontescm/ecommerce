<?php

namespace Hcode;

class PageAdmin extends Page {

	public function __construct($opts = array(), $tpl_dir = "/views/admin/")
	{		
		/*para evitar refazer todos os métodos da classe Pai Page, utiliza-se o parent para reutilizar os métodos construtores e outros da classe Pai*/
		parent::__construct($opts, $tpl_dir);

	}

}

?>