{*
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
*}

{extends file='catalog/listing/product-list.tpl'}

{block name='product_list_header'}
    {$smarty.block.parent}
    <p>{l s='You will find on this page the products that you have ordered most often' mod='myfrequentshopping'}</p>
{/block}
