<?php /** @noinspection PhpMultipleClassDeclarationsInspection - there is only one Configuration class at runtime. */

/**
 * © 2021 Boekuwzending
 **/

use Boekuwzending\PrestaShop\Repository\BoekuwzendingOrderRepository;
use Boekuwzending\PrestaShop\Service\BoekuwzendingClient;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . "/vendor/autoload.php";

class Boekuwzending extends CarrierModule
{
    /**
     * @var BoekuwzendingClient
     */
    private $boekuwzendingClient;

    /**
     * @var BoekuwzendingOrderRepository
     */
    private $orderRepository;

    public function __construct(BoekuwzendingClient $boekuwzendingClient, BoekuwzendingOrderRepository $orderRepository)
    {
        $this->boekuwzendingClient = $boekuwzendingClient;
        $this->orderRepository = $orderRepository;

        $this->name = 'boekuwzending';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Boekuwzending';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Boekuwzending.com');
        $this->description = $this->l('Add a description for your module to help merchants to understand what he should use your module for (200 characters maximum).');

        $this->ps_versions_compliancy = array('min' => '1.7.7', 'max' => _PS_VERSION_);
    }

    /**
     * This hook gets fired once, after an order has been placed.
     * @param $params
     */
    public function hookActionValidateOrder($params): void
    {
        $order = $params["order"];
        $prestaOrderId = (int)$order->id;

        PrestaShopLogger::addLog('Boekuwzending::hookActionValidateOrder(): sending order to Boekuwzending', 1, null, 'Order', $prestaOrderId, true);

        try {
            $buzOrder = $this->boekuwzendingClient->createOrder($order);
            $buzOrderId = $buzOrder->getId();

            if (!$this->orderRepository->insert($prestaOrderId, $buzOrderId)) {
                PrestaShopLogger::addLog('Boekuwzending::hookActionValidateOrder(): Boekuwzending order not created', 3, null, 'Order', $prestaOrderId, true);
            } else {
                PrestaShopLogger::addLog('Boekuwzending::hookActionValidateOrder(): Boekuwzending order created, id: "' . $buzOrderId . '"', 1, null, 'Order', $prestaOrderId, true);
            }
        } catch (Exception $ex) {
            PrestaShopLogger::addLog('Boekuwzending::hookActionValidateOrder(): exception: ' . $ex, 3, null, 'Order', $prestaOrderId, true);
        }
    }

    // TODO: < 1.7.7: displayAdminOrderContentOrder instead of OrderTabLinks/Content
    public function hookDisplayAdminOrderTabLink()
    {
        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/orderTabLink.tpl');
    }

    public function hookDisplayAdminOrderTabContent($params)
    {
        $orderId = $params["id_order"];
        $boekuwzendingOrders = [];

        try {
            $boekuwzendingOrders = $this->orderRepository->findByOrderId($orderId);
        } catch (Exception $ex) {
            PrestaShopLogger::addLog('Boekuwzending::hookDisplayAdminOrderTabContent(): exception while retrieving orders: ' . $ex, 3, null, 'Order', $orderId, true);
        }

        $this->context->smarty->assign('orderId', $orderId);
        $this->context->smarty->assign('baseUrl', $this->boekuwzendingClient->getBoekuwzendingOrderUrl());
        $this->context->smarty->assign('orders', $boekuwzendingOrders);

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/orderTabContent.tpl');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install(): bool
    {
        if (extension_loaded('curl') !== true) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $carrier = $this->addCarrier();
        $this->addZones($carrier);
        $this->addGroups($carrier);
        $this->addRanges($carrier);
        Configuration::updateValue('BOEKUWZENDING_LIVE_MODE', false);

        include(__DIR__ . '/sql/install.php');

        return parent::install() &&
            $this->registerHook('actionValidateOrder') &&

            // TODO: < 1.7.7: displayAdminOrderContentOrder
            $this->registerHook('displayAdminOrderTabLink') &&
            $this->registerHook('displayAdminOrderTabContent');
    }

    public function uninstall(): bool
    {
        Configuration::deleteByName('BOEKUWZENDING_LIVE_MODE');
        Configuration::deleteByName('BOEKUWZENDING_CLIENT_ID');
        Configuration::deleteByName('BOEKUWZENDING_CLIENT_SECRET');
        Configuration::deleteByName('BOEKUWZENDING_FIXED_PRICE');

        include(__DIR__ . '/sql/uninstall.php');

        // TODO: remove carrier

        return parent::uninstall() &&
            $this->unregisterHook('actionValidateOrder') &&

            // TODO: < 1.7.7: displayAdminOrderContentOrder
            $this->unregisterHook('displayAdminOrderTabLink') &&
            $this->unregisterHook('displayAdminOrderTabContent');
    }

    /**
     * PrestaShop method, called through reflection, show and/or processes the configuration form.
     */
    public function getContent(): string
    {
        /**
         * If values have been submitted in the form, process.
         */
        if ((Tools::isSubmit('submitBoekuwzendingModule')) === true) {
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
        $helper->submit_action = 'submitBoekuwzendingModule';
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
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'BOEKUWZENDING_LIVE_MODE',
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
                        'type' => 'text',
                        //'desc' => $this->l('Enter a valid email address'),
                        'name' => 'BOEKUWZENDING_CLIENT_ID',
                        'label' => $this->l('Client ID'),
                        'required' => true,
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'BOEKUWZENDING_CLIENT_SECRET',
                        'label' => $this->l('Client Secret'),
                        'required' => true,
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-euro"></i>',
                        //'desc' => $this->l('Enter a valid email address'),
                        'name' => 'BOEKUWZENDING_FIXED_PRICE',
                        'label' => $this->l('Shipping cost without matrix'),
                        'required' => true,
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
            'BOEKUWZENDING_LIVE_MODE' => Configuration::get('BOEKUWZENDING_LIVE_MODE', false),
            'BOEKUWZENDING_CLIENT_ID' => Configuration::get('BOEKUWZENDING_CLIENT_ID', null),
            'BOEKUWZENDING_CLIENT_SECRET' => Configuration::get('BOEKUWZENDING_CLIENT_SECRET', null),
            'BOEKUWZENDING_FIXED_PRICE' => Configuration::get('BOEKUWZENDING_FIXED_PRICE', null),
        );
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

    public function getOrderShippingCost($params, $shipping_cost)
    {
        return Configuration::get('BOEKUWZENDING_FIXED_PRICE', $shipping_cost);
    }

    public function getOrderShippingCostExternal($params)
    {
        return true;
    }

    protected function addCarrier()
    {
        $carrier = new Carrier();

        $carrier->name = $this->l('Boekuwzending');
        $carrier->is_module = true;
        $carrier->active = 0;
        $carrier->range_behavior = 1;
        $carrier->need_range = 1;
        $carrier->shipping_external = true;
        $carrier->range_behavior = 0;
        $carrier->external_module_name = $this->name;
        $carrier->shipping_method = 2;

        foreach (Language::getLanguages() as $lang) {
            $carrier->delay[$lang['id_lang']] = $this->l('Boekuwzending');
        }

        if (true === $carrier->add()) {
            @copy(__DIR__ . '/views/img/carrier_image.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int)$carrier->id . '.jpg');
            Configuration::updateValue('BOEKUWZENDING_CARRIER_ID', (int)$carrier->id);
            return $carrier;
        }

        return false;
    }

    protected function addGroups($carrier)
    {
        $groups_ids = array();
        $groups = Group::getGroups(Context::getContext()->language->id);
        foreach ($groups as $group) {
            $groups_ids[] = $group['id_group'];
        }

        $carrier->setGroups($groups_ids);
    }

    protected function addRanges($carrier)
    {
        $range_price = new RangePrice();
        $range_price->id_carrier = $carrier->id;
        $range_price->delimiter1 = '0';
        $range_price->delimiter2 = '10000';
        $range_price->add();

        $range_weight = new RangeWeight();
        $range_weight->id_carrier = $carrier->id;
        $range_weight->delimiter1 = '0';
        $range_weight->delimiter2 = '10000';
        $range_weight->add();
    }

    protected function addZones($carrier)
    {
        $zones = Zone::getZones();

        foreach ($zones as $zone) {
            $carrier->addZone($zone['id_zone']);
        }
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }
}
