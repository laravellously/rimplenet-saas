<?php

class Rimplenet_Base_MLM_Matrix_Api
{

	private $plugin_name;

	private $version;

	public function __construct()
	{
		$this->plugin_name = $plugin_name ?? 'RimplenetIn';
		$this->version = $version ?? 'v1';
		$this->load_required_files();
	}
	private function load_required_files()
	{
		//Add Required Files to Load
		// require_once plugin_dir_path(dirname(__FILE__)) . 'mail/password-reset-mail.php';
        // require_once plugin_dir_path(dirname(__FILE__)) . 'mail/verify-email-mail.php';
	}
}


$Rimplenet_Base_MLM_Matrix_Api = new Rimplenet_Base_MLM_Matrix_Api();