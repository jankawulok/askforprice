<?php
/**
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
*  @author    PrestaShop SA <contact@prestashop.com>, Jan Kawulok <jan@kawulok.com.pl>
*  @copyright 2007-2015 PrestaShop SA, Jan Kawulok <jan@kawulok.com.pl>
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class AskforPriceformModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
    * Start forms process
    * @see FrontController::postProcess()
    */
    public function postProcess()
    {
        if (Tools::isSubmit('submitMessage'))
        {
            $message = Tools::getValue('message'); // Html entities is not usefull, iscleanHtml check there is no bad html tags.
            if (!($from = trim(Tools::getValue('from'))) || !Validate::isEmail($from))
                $this->errors[] = Tools::displayError('Invalid email address.');
            elseif (!$message)
                $this->errors[] = Tools::displayError('The message cannot be blank.');
            elseif (!Validate::isCleanHtml($message))
                $this->errors[] = Tools::displayError('Invalid message');
            elseif (!empty($file_attachment['name']) && $file_attachment['error'] != 0)
                $this->errors[] = Tools::displayError('An error occurred during the file-upload process.');
            elseif (!empty($file_attachment['name']) && !in_array(Tools::strtolower(substr($file_attachment['name'], -4)), $extension) && !in_array(Tools::strtolower(substr($file_attachment['name'], -5)), $extension))
                $this->errors[] = Tools::displayError('Bad file extension');
            else
            {
                $customer = $this->context->customer;
                if (!$customer->id)
                    $customer->getByEmail($from);
                $id_contact = (int)Configuration::get('ASKFORPRICE_CONTACT');
                $contact = new Contact($id_contact);
                if ($contact->customer_service)
                {
                    if ((int)$id_customer_thread)
                    {
                        $ct = new CustomerThread($id_customer_thread);
                        $ct->status = 'open';
                        $ct->id_lang = (int)$this->context->language->id;
                        $ct->id_contact = (int)$id_contact;
                        $ct->id_order = (int)$id_order;
                        if ($id_product = (int)Tools::getValue('id_product'))
                            $ct->id_product = $id_product;
                        $ct->update();
                    }
                    else
                    {
                        $ct = new CustomerThread();
                        if (isset($customer->id))
                            $ct->id_customer = (int)$customer->id;
                        $ct->id_shop = (int)$this->context->shop->id;
                        $ct->id_order = (int)$id_order;
                        if ($id_product = (int)Tools::getValue('id_product'))
                            $ct->id_product = $id_product;
                        $ct->id_contact = (int)$id_contact;
                        $ct->id_lang = (int)$this->context->language->id;
                        $ct->email = $from;
                        $ct->status = 'open';
                        $ct->token = Tools::passwdGen(12);
                        $ct->add();
                    }

                    if ($ct->id)
                    {
                        $cm = new CustomerMessage();
                        $cm->id_customer_thread = $ct->id;
                        $cm->message = $message;
                        if (isset($file_attachment['rename']) && !empty($file_attachment['rename']) && rename($file_attachment['tmp_name'], _PS_UPLOAD_DIR_.basename($file_attachment['rename'])))
                        {
                            $cm->file_name = $file_attachment['rename'];
                            @chmod(_PS_UPLOAD_DIR_.basename($file_attachment['rename']), 0664);
                        }
                        $cm->ip_address = (int)ip2long(Tools::getRemoteAddr());
                        $cm->user_agent = $_SERVER['HTTP_USER_AGENT'];
                        if (!$cm->add())
                            $this->errors[] = Tools::displayError('An error occurred while sending the message.');
                    }
                    else
                        $this->errors[] = Tools::displayError('An error occurred while sending the message.');
                }

                if (!count($this->errors))
                {
                    $mailpaht = _PS_MODULE_DIR_.'askforprice/mails/';
                    $message= Tools::nl2br(stripslashes($message));

                    $var_list = array(
                                    '{message}' => $message,
                                    '{email}' =>  $from,
                                    '{id_product}' => (int)Tools::getValue('id_product'),
                                    '{product_name}' => '',
                                    '{product_qty}' => (int)Tools::getValue('qty'),
                                    '{suggested_price}' => (float)Tools::getValue('price')
                                );
                    $id_product = (int)Tools::getValue('id_product');

                    if (isset($ct) && Validate::isLoadedObject($ct) && $ct->id_order)
                    {
                        $order = new Order((int)$ct->id_order);
                        $var_list['{order_name}'] = $order->getUniqReference();
                        $var_list['{id_order}'] = (int)$order->id;
                    }

                    if ($id_product)
                    {
                        $product = new Product((int)$id_product);
                        if (Validate::isLoadedObject($product) && isset($product->name[Context::getContext()->language->id]))
                            $var_list['{product_name}'] = $product->name[Context::getContext()->language->id];
                    }

                    if (empty($contact->email))
                        Mail::Send($this->context->language->id, 'askforprice_form', ((isset($ct) && Validate::isLoadedObject($ct)) ? sprintf(Mail::l('Your message has been correctly sent #ct%1$s #tc%2$s'), $ct->id, $ct->token) : Mail::l('Your message has been correctly sent')), $var_list, $from, null, null, null, $mailpaht);
                    else
                    {
                        if (!Mail::Send($this->context->language->id, 'askforprice', Mail::l('Message from contact form').' [no_sync]',
                            $var_list, $contact->email, $contact->name, $from, ($customer->id ? $customer->firstname.' '.$customer->lastname : ''),
                                    null,null, $mailpaht) ||
                                !Mail::Send($this->context->language->id, 'askforprice_form', ((isset($ct) && Validate::isLoadedObject($ct)) ? sprintf(Mail::l('Your message has been correctly sent #ct%1$s #tc%2$s'), $ct->id, $ct->token) : Mail::l('Your message has been correctly sent')), $var_list, $from, null, $contact->email, $contact->name, null, null, $mailpaht))
                                    $this->errors[] = Tools::displayError('An error occurred while sending the message.');
                    }
                }

                if (count($this->errors) > 1)
                    array_unique($this->errors);
                elseif (!count($this->errors))
                    $this->context->smarty->assign('confirmation', 1);
            }
        }
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_.'contact-form.css');
        $this->addJS(_THEME_JS_DIR_.'contact-form.js');
        $this->addJS(_PS_JS_DIR_.'validate.js');
    }

    /**
    * Assign template vars related to page content
    * @see FrontController::initContent()
    */
    public function initContent()
    {
        parent::initContent();
        $id_product = (int)Tools::getValue('id_product');
        $email = Tools::safeOutput(Tools::getValue('from',
        ((isset($this->context->cookie) && isset($this->context->cookie->email) && Validate::isEmail($this->context->cookie->email)) ? $this->context->cookie->email : '')));
        $this->context->smarty->assign(array(
            'errors' => $this->errors,
            'id_contact' => (int)Configuration::get('ASKFORPRICE_CONTACT'),
            'id_product' => $id_product,
            'product_price' => Product::getPriceStatic($id_product, true, null, 2),
            'email' => $email,
            'priceDisplay' => Product::getTaxCalculationMethod()
        ));

        $this->context->smarty->assign(array(
            'message' => html_entity_decode(Tools::getValue('message'))
        ));

        $this->setTemplate('form.tpl');
    }

}