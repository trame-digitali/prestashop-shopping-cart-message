<?php
/**
 * 2007-2020 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Clmessage extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'clmessage';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Trame Digitali';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Shopping Cart Message');
        $this->description = $this->l('Display a message in Shopping Cart page');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (Shop::isFeatureActive()) Shop::setContext(Shop::CONTEXT_ALL);
        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            //$values[] = Tools::getValue('SOMETEXT_TEXT_'.$lang['id_lang']);
            Configuration::updateValue('CLMESSAGE_CART_TEXT_' . $lang['id_lang'], '', true);
        }

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayShoppingCart') &&
            $this->registerHook('displayCheckoutSummaryTop');
    }

    public function uninstall()
    {

        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            //$values[] = Tools::getValue('SOMETEXT_TEXT_'.$lang['id_lang']);
            Configuration::deleteByName('CLMESSAGE_CART_TEXT_' . $lang['id_lang']);
        }

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
        if (((bool)Tools::isSubmit('submitClmessageModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
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
        $helper->submit_action = 'submitClmessageModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
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
                        'col' => 9,
                        'type' => 'textarea',
                        'desc' => $this->l('Questo messaggio verrà visualizzato sopra il riepilogo del tuo ordine.'),
                        'name' => 'CLMESSAGE_CART_TEXT',
                        'label' => $this->l('Messaggio riepilogo'),
                        'autoload_rte' => true,
                        'lang' => true
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

        $languages = Language::getLanguages(false);
        $values = array();

        foreach ($languages as $lang) {
            $values['CLMESSAGE_CART_TEXT'][$lang['id_lang']] = Configuration::get('CLMESSAGE_CART_TEXT_' . $lang['id_lang']);

        }
        return $values;

    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            Configuration::updateValue('CLMESSAGE_CART_TEXT_' . $lang['id_lang'], Tools::getValue('CLMESSAGE_CART_TEXT_' . $lang['id_lang']), true);
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function hookDisplayShoppingCart()
    {

        $some_string = Configuration::get('CLMESSAGE_CART_TEXT_' . $this->context->language->id);

        $this->context->smarty->assign([
            'module_dir' => $this->_path,
            'cart_message' => $some_string
        ]);

        return $this->display(__FILE__, 'message.tpl');

    }

    public function hookDisplayCheckoutSummaryTop()
    {

        $some_string = Configuration::get('CLMESSAGE_CART_TEXT_' . $this->context->language->id);

        $this->context->smarty->assign([
            'module_dir' => $this->_path,
            'cart_message' => $some_string
        ]);

        return $this->display(__FILE__, 'message.tpl');

    }
}
