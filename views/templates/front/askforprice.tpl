{*
* 2007-2014 PrestaShop
* 2016 Jan Kawulok
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
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>, Jan Kawulok <jan@kawulok.com.pl>
*  @copyright  2007-2014 PrestaShop SA, 2016 Jan Kawulok
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{if !isset($ASKFORPRICE_MINIMAL_PRICE) || ($ASKFORPRICE_MINIMAL_PRICE <= $askforprice_product_price)}
<p class="buttons_bottom_block no-print">
    <a href="{$askforprice_form_url}" rel="{$askforprice_form_url}" class="button btn btn-default button-medium" id="askforprice_button"><span>{l s='Ask for price' mod='askforprice'}</span></a>
</p>
{/if}