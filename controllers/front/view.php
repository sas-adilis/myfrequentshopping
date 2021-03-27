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

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

class MyFrequentShoppingViewModuleFrontController extends ProductListingFrontControllerCore
{
	public $auth = true;

	const MODULE_NAME = 'myfrequentshopping';

	/**
	 * @var false|Module
	 */
	private $module;

	public function __construct()
	{
		$this->module = Module::getInstanceByName(self::MODULE_NAME);
		if (!$this->module->active) {
			Tools::redirect('index');
		}
		$this->page_name = 'module-' . $this->module->name . '-' . Dispatcher::getInstance()->getController();
		parent::__construct();
		$this->controller_type = 'modulefront';
	}

	/**
	 * Assigns module template for page content.
	 * @param string $template Template filename
	 * @throws PrestaShopException
	 */
	public function setTemplate($template, $params = array(), $locale = null)
	{
		if (strpos($template, 'module:') === 0) {
			$this->template = $template;
		} else {
			parent::setTemplate($template, $params, $locale);
		}
	}

    protected function getProductSearchQuery()
    {
        $query = new ProductSearchQuery();
        $query
            ->setQueryType('my-frequent-shopping')
            ->setSortOrder(new SortOrder('product', 'position', 'asc'));
        return $query;
    }

	public function initContent()
	{
		parent::initContent();
		$this->doProductSearch('module:myfrequentshopping/views/templates/front/view.tpl');
	}

    protected function getDefaultProductSearchProvider()
    {
        require_once __DIR__ . '/../../adapters/MyFrequentShoppingProductSearchProvider.php';
        return new MyFrequentShoppingProductSearchProvider(
            $this->getTranslator()
        );
    }

	public function getListingLabel()
	{
		return $this->module->l('My frequent shopping');
	}

	public function getBreadcrumbLinks()
	{
		$breadcrumb = parent::getBreadcrumbLinks();
		$breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
		return $breadcrumb;
	}
}
