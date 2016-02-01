{*
* 2007-2015 PrestaShop
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
{capture name=path}{l s='Ask for price' mod='askforprice'}{/capture}
{if isset($confirmation)}
    <p class="alert alert-success">{l s='Your message has been successfully sent to our team.'  mod='askforprice'}</p>

{elseif isset($alreadySent)}
    <p class="alert alert-warning">{l s='Your message has already been sent.'  mod='askforprice'}</p>
{else}
    {include file="$tpl_dir./errors.tpl"}

    <form action="{$request_uri}" method="post" class="contact-form-box" enctype="multipart/form-data">
        <fieldset>
        <h3 class="page-subheading">{l s='Negotiate product price'  mod='askforprice'}</h3>
        <div class="clearfix">
            <div class="col-xs-12 col-md-3">
                <input type="hidden" name="id_contact" value="{$id_contact}" />
                <input type="hidden" name="id_product" value="{$id_product}" />
                <p class="form-group">
                    <label for="email">{l s='Email address' mod='askforprice'}</label>
                    {if isset($customerThread.email)}
                        <input class="form-control grey" type="text" id="email" name="from" value="{$customerThread.email|escape:'html':'UTF-8'}" readonly="readonly" />
                    {else}
                        <input class="form-control grey validate" type="text" id="email" name="from" data-validate="isEmail" value="{$email|escape:'html':'UTF-8'}" />
                    {/if}
                </p>
                <p class="form-group">
                    <label for="qty" class="control-label">{l s='Quantity wanted' mod='askforprice'}</label>
                    <div class="input-group" style="max-width: 115px">
                        <input class="form-control validate" type="text" id="qty" name="qty" value="1" data-validate="isNumber"/>
                        <span class="input-group-addon">{l s='qty' mod='askforprice'}</span>   
                    </div>
                </p>
                <div class="form-group">
                    <label for="price">{l s='My price' mod='askforprice'} {if $priceDisplay == 1}{l s='tax excl.' mod='askforprice'}{else}{l s='tax incl.' mod='askforprice'}{/if}</label>
                    <div class="input-group" style="max-width: 115px">
                        <input class="form-control validate" type="text" id="price" name="price" value="{$product_price}" data-validate="isNumber" />
                        <span class="input-group-addon">{$currency->iso_code}</span>   
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-md-9">
                <div class="form-group">
                    <label for="message">{l s='Message' mod='askforprice'}</label>
                    <textarea class="form-control" id="message" name="message">{if isset($message)}{$message|escape:'html':'UTF-8'|stripslashes}{/if}</textarea>
                </div>
            </div>
        </div>
        <div class="submit">
            <button type="submit" name="submitMessage" id="submitMessage" class="button btn btn-default button-medium"><span>{l s='Send' mod='askforprice'}<i class="icon-chevron-right right"></i></span></button>
        </div>
    </fieldset>
</form>
{/if}