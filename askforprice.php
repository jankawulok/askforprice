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

if (!defined('_PS_VERSION_')) {
    exit;
}

class Askforprice extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'askforprice';
        $this->tab = 'merchandizing';
        $this->version = '1.0.0';
        $this->author = 'Jan Kawulok';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ask for price');
        $this->description = $this->l('Negotiate product price with customer');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('ASKFORPRICE_LIVE_MODE', false);
        

        return parent::install() &&
            $this->addContact() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayProductButtons');
    }

    public function uninstall()
    {
        Configuration::deleteByName('ASKFORPRICE_LIVE_MODE');
        Configuration::deleteByName('ASKFORPRICE_CONTACT');
        Configuration::deleteByName('ASKFORPRICE_MINIMAL_PRICE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitAskforpriceModule')) == true) {
            $this->postProcess();
        }
        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAskforpriceModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'ASKFORPRICE_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Select contact'),
                        'name' => 'ASKFORPRICE_CONTACT',
                        'label' => $this->l('Contact'),
                        'required' => true,
                        'options' => array(
                            'query' => $this->getShopContacts(),
                            'id' => 'id_contact',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'col' =>3,
                        'type' => 'text',
                        'name' => 'ASKFORPRICE_MINIMAL_PRICE',
                        'label' => $this->l('Minimall price'),
                        'desc' => $this->l('Leave empty to disable'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'ASKFORPRICE_LIVE_MODE' => Configuration::get('ASKFORPRICE_LIVE_MODE', true),
            'ASKFORPRICE_CONTACT' => Configuration::get('ASKFORPRICE_CONTACT', ''),
            'ASKFORPRICE_MINIMAL_PRICE' => Configuration::get('ASKFORPRICE_MINIMAL_PRICE', null),
        );
    }

    /**
     * Get available shop contacts.
     */
    protected function getShopContacts()
    {
        return Contact::getContacts($this->context->language->id);
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayProductButtons($params)
    {
        $taxes = Product::getTaxCalculationMethod();
        $id_product = (int)Tools::getValue('id_product');
        if ($taxes == 0 || $taxes == 2)
        {
            $askforprice_product_price= Product::getPriceStatic(
                $id_product,
                true,
                null,
                2
            );
        } elseif ($taxes == 1)
        {
            $askforprice_product_price = Product::getPriceStatic(
                $id_product,
                false,
                null,
                2
            );
        }
        $this->context->smarty->assign(
        array(
            'ASKFORPRICE_MINIMAL_PRICE' => Configuration::get('ASKFORPRICE_MINIMAL_PRICE', null),
            'askforprice_product_price' => $askforprice_product_price,
            'askforprice_form_url' => $this->context->link->getModuleLink('askforprice', 'form').'&id_product='.$id_product
        )
        );
        return $this->display($this->_path, 'views/templates/front/askforprice.tpl');
    }

    protected function addContact()
    {
        $contact = new Contact();
        $contact->name[Configuration::get('PS_LANG_DEFAULT')] = "Price negotiation";
        $contact->description[Configuration::get('PS_LANG_DEFAULT')] = "Negotiate price with seller";
        $contact->email = Configuration::get('PS_SHOP_EMAIL');
        if ($contact->add()) {
            Configuration::updateValue('ASKFORPRICE_CONTACT', $contact->id);
        };
        
        return $contact->id;
    }


}
