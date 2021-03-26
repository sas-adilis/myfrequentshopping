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

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrderFactory;
use Symfony\Component\Translation\TranslatorInterface;

class MyFrequentShoppingProductSearchProvider implements ProductSearchProviderInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var SortOrderFactory
     */
    private $sortOrderFactory;

	public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
        $this->sortOrderFactory = new SortOrderFactory($this->translator);
	}

    /**
     * @param ProductSearchContext $context
     * @param ProductSearchQuery $query
     *
     * @return ProductSearchResult
     */
    public function runQuery(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
        $sortBySales = (new SortOrder('product', 'sales', 'desc'))->setLabel(
            $this->translator->trans('Sales, highest to lowest', array(), 'Shop.Theme.Catalog')
        );

        if (!Tools::getValue('order', 0)) {
            $query->setSortOrder($sortBySales);
        }

        if (!$products = self::getBestSales(
            $context->getIdLang(),
            $query->getPage(),
            $query->getResultsPerPage(),
            false,
            $query->getSortOrder()->toLegacyOrderBy(),
            $query->getSortOrder()->toLegacyOrderWay()
        )) {
            $products = array();
        }

        $count = (int) ProductSale::getNbSales();

        $result = new ProductSearchResult();

        if (!empty($products)) {
            $result
                ->setProducts($products)
                ->setTotalProductsCount($count);

            $result->setAvailableSortOrders(
                array(
                    $sortBySales,
                    (new SortOrder('product', 'name', 'asc'))->setLabel(
                        $this->translator->trans('Name, A to Z', array(), 'Shop.Theme.Catalog')
                    ),
                    (new SortOrder('product', 'name', 'desc'))->setLabel(
                        $this->translator->trans('Name, Z to A', array(), 'Shop.Theme.Catalog')
                    ),
                    (new SortOrder('product', 'price', 'asc'))->setLabel(
                        $this->translator->trans('Price, low to high', array(), 'Shop.Theme.Catalog')
                    ),
                    (new SortOrder('product', 'price', 'desc'))->setLabel(
                        $this->translator->trans('Price, high to low', array(), 'Shop.Theme.Catalog')
                    ),
                )
            );
        }

        return $result;
    }

	/**
	 * Get required informations on best sales products.
	 *
	 * @param int $idLang Language id
	 * @param int $pageNumber Start from (optional)
	 * @param int $nbProducts Number of products to return (optional)
	 * @param bool $count
	 * @param null $orderBy
	 * @param null $orderWay
	 * @return array|bool from Product::getProductProperties
	 *                    `false` if failure
	 * @throws PrestaShopDatabaseException
	 */
	public static function getBestSales($idLang,  $pageNumber = 0, $nbProducts = 10, $count = false, $orderBy = null, $orderWay = null)
	{
		$context = Context::getContext();
		if ($pageNumber < 1) {
			$pageNumber = 1;
		}
		if ($nbProducts < 1) {
			$nbProducts = 10;
		}

		$invalidOrderBy = !Validate::isOrderBy($orderBy);
		if ($invalidOrderBy || null === $orderBy) {
			$orderBy = 'quantity';
			$orderByPrefix = 'ps';
		}

		if ($orderBy == 'date_add' || $orderBy == 'date_upd') {
			$orderByPrefix = 'product_shop';
		}

		$invalidOrderWay = !Validate::isOrderWay($orderWay);
		if ($invalidOrderWay || null === $orderWay || $orderBy == 'sales') {
			$orderWay = 'DESC';
		}


		/* Minimum query for count */
		$sql = new DbQuery();
		$sql->from('order_detail', 'od');
		$sql->innerJoin('orders', 'o', 'od.id_order = o.id_order');
		$sql->groupBy('od.product_id');
		$sql->leftJoin('product', 'p', 'od.product_id = p.id_product');
		$sql->join(Shop::addSqlAssociation('product', 'p'));
		$sql->where('product_shop.`active` = 1');
		$sql->where('product_shop.`visibility` IN ("both", "catalog")');
		$sql->where('o.`id_customer` = '.(int)$context->customer->id );
		$sql->where('o.`valid` = 1' );
		$sql->groupBy('od.product_id');

		if (Group::isFeatureActive()) {
			$groups = FrontController::getCurrentCustomerGroups();
			$subquery = new DbQuery();
			$subquery->select('1');
			$subquery->from('category_product', 'cp');
			$subquery->innerJoin(
				'category_group',
				'cg',
				'
				cp.id_category = cg.id_category AND
				cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '=' . (int)Configuration::get('PS_UNIDENTIFIED_GROUP'))

			);
			$subquery->where('cp.`id_product` = p.`id_product`');
			$sql->where('EXISTS('.$subquery->__toString().')');
		}

		if ($count) {
			$sql->select('COUNT(p.`id_product`) AS nb');
			return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
		}


		$now = date('Y-m-d') . ' 00:00:00';
		$nb_days_new_product = (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT');

		$sql->select(
			'p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`,
			pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`,
			pl.`name`, pl.`available_now`, pl.`available_later`, image_shop.`id_image` id_image, il.`legend`, m.`name` AS manufacturer_name,
            SUM(od.`product_quantity`) as sales, (DATEDIFF(product_shop.`date_add`,
                DATE_SUB(
                    "' . $now . '",
                    INTERVAL ' . $nb_days_new_product . ' DAY
                )
            ) > 0) as new'
		);
		$sql->leftJoin(
			'product_lang',
			'pl',
			'
            p.`id_product` = pl.`id_product`
            AND pl.`id_lang` = ' . (int)$idLang . Shop::addSqlRestrictionOnLang('pl')
		);

		$sql->leftJoin(
			'image_shop',
			'image_shop',
			'
			image_shop.`id_product` = p.`id_product` AND
			image_shop.cover=1 AND
			image_shop.id_shop=' . (int)$context->shop->id
		);
		$sql->leftJoin(
			'image_lang',
			'il',
			'image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int)$idLang
		);
		$sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');
		$sql->join(Product::sqlStock('p', 0));

		if (Group::isFeatureActive()) {
			$sql->where('EXISTS('.$subquery->__toString().')');
		}

		if (Combination::isFeatureActive()) {
			$sql->select('product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity');
			$sql->select('IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute');
			$sql->leftJoin(
				'product_attribute_shop',
				'product_attribute_shop',
				'
				p.`id_product` = product_attribute_shop.`id_product` AND
				product_attribute_shop.`default_on` = 1 AND
				product_attribute_shop.id_shop=' . (int)$context->shop->id
			);
		}

		$sql->orderBy((isset($orderByPrefix) ? pSQL($orderByPrefix) . '.' : '') . '`' . pSQL($orderBy) . '` ' . pSQL($orderWay));
		$sql->limit($nbProducts, (int)(($pageNumber - 1) * $nbProducts));

		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

		if (!$result) {
			return false;
		}

		if ($orderBy == 'price') {
			Tools::orderbyPrice($result, $orderWay);
		}
		$products_ids = array();
		foreach ($result as $row) {
			$products_ids[] = $row['id_product'];
		}
		// Thus you can avoid one query per product, because there will be only one query for all the products of the cart
		Product::cacheFrontFeatures($products_ids, $idLang);

		return Product::getProductsProperties((int) $idLang, $result);
	}
}
