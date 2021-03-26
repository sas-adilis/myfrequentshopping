<?php

/**
* 2021 Adilis
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
* This code is provided as is without any warranty.
* No promise of being safe or secure
*
* @author   Achard Julien <contact@adilis.fr>
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

if (!defined('_PS_VERSION_')) {
	exit;
}

class MyFrequentShopping extends \Module
{

	function __construct()
	{
		$this->name = 'myfrequentshopping';
		$this->author = 'Adilis';
		$this->need_instance = 0;
		$this->bootstrap = true;
		$this->tab = 'front_office_features';
		$this->version = '1.0.0';
		$this->displayName = $this->l('My frequent shopping');
		$this->description = $this->l('Frequent shopping page in customer account');
		$this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
		$this->controllers = array('view');

		parent::__construct();
	}

	public function install() {
	    return
			parent::install() &&
			$this->registerHook('displayCustomerAccount');
	}

	public function hookDisplayCustomerAccount() {
		return $this->fetch('module:' . $this->name . '/views/templates/hook/my-account.tpl');
	}
}