<?php

abstract class WC_Spell_Gateway_Abstract extends WC_Payment_Gateway
{
    public $id = "klix-payments";
    public $title = "Klix";
    public $method_title = "Klix E-commerce Gateway";
    public $description = " ";
    public $method_description = "";
    public $debug = true;
    public $supports = array('products', 'refunds');

    /**
     * @var SpellAPI
     */
    private $cached_api;

    /**
     * @var WC_Spell_Gateway_Payment_Helper
     */
    private $payment_helper;

    /**
     * @var array|mixed
     */
    private $global_mapping;

    public function __construct()
    {
        // TODO: Set icon. Probably can be an external URL.
        $this->init_form_fields();
        $this->init_settings();

        $this->payment_helper = new WC_Spell_Gateway_Payment_Helper();
        $this->shared_settings = new WC_Spell_Gateway_Payment_Settings();
        $this->global_mapping = $GLOBALS['spell_payment_groups'] ?? array();

        $this->hid = $this->shared_settings->get_option('hid');
        $this->label = isset($this->global_mapping[$this->id]) ? $this->global_mapping[$this->id]['label'] : $this->shared_settings->get_option('label');
        $this->icon = isset($this->global_mapping[$this->id]) ? $this->global_mapping[$this->id]['logo'] : null;

        $this->method_desc = $this->shared_settings->get_option('method_desc');
        $this->title = $this->label;
        $this->method_description = $this->method_desc;

        if ($this->title === '') {
            $ptitle = "Select Payment Method";
            $this->title = $ptitle;
        };

        if ($this->method_description === '') {
            $pmeth = "Choose payment method on next page";
            $this->method_description = $pmeth;
        };

        add_action(
            'woocommerce_update_options_payment_gateways_klix-payments',
            array($this, 'process_admin_options')
        );
        str_replace(
            'https:',
            'http:',
            add_query_arg('wc-api', 'WC_Spell_Gateway', home_url('/'))
        );

        add_action(
            'woocommerce_api_wc_gateway_klix',
            array($this, 'handle_callback')
        );
    }

    public function spell_api()
    {
        if (!$this->cached_api) {
            $api_client = new WC_Spell_Gateway_Payment_Api();
            $this->cached_api = $api_client->spell_api();
        }

        return $this->cached_api;
    }

    private function log_order_info($msg, $o)
    {
        $this->spell_api()->log_info($msg . ': ' . $o->get_order_number());
    }

    private function retrieve_payment_id($order_id)
    {
        $payment_id = WC()->session->get(
            'spell_payment_id_' . $order_id
        );
        if (!$payment_id) {
            $input = json_decode(file_get_contents('php://input'), true);
            if($input !== null) {
                $payment_id = array_key_exists('id', $input) ? $input['id'] : '';
            }
        }
        
        return $payment_id;
    }

    private function confirm_order($o,$purchase)
    {
        if (!$o->is_paid()) {
            $payment_methods=get_transient('spell-payment-methods');
            $payment_method_name=$payment_methods['names'][$purchase['transaction_data']['payment_method']];
            $o->payment_complete($purchase['id']);
            $o->set_payment_method_title($payment_method_name);
            $o->save();
            $o->add_order_note(
                sprintf(__('Payment Successful. Transaction ID: %s', 'woocommerce'), $purchase['id'])
            );
        }
        WC()->cart->empty_cart();
        $this->log_order_info('payment processed', $o);
    }

    public function handle_callback()
    {
        $this->spell_api()->log_info('received callback: ' . print_r($_GET, true));
        $GLOBALS['wpdb']->get_results(
            "SELECT GET_LOCK('spell_payment', 15);"
        );

        global $woocommerce;
        
        $o = wc_get_order($_GET["id"]);

        if($o===false)
        {
            wp_redirect(wc_get_page_permalink('shop'));
            return;
        }

        $payment_redirect = $this->get_return_url($o);
        $payment_id=$this->retrieve_payment_id($_GET['id']);
        
        if ($payment_id !== "") {
            $purchase=$this->spell_api()->get_payment($payment_id);
            if ($purchase && $purchase['status'] == 'paid') {
                $this->confirm_order($o,$purchase);
            } 
            else {
                if ($o->get_status() === "pending") {
                    $o->update_status(
                        'wc-failed',
                        __('Order not paid.')
                    );
                    $this->log_order_info('payment not successful', $o);
                }
                $payment_redirect = $woocommerce->cart->get_cart_url();
            }
        }
        

        $GLOBALS['wpdb']->get_results(
            "SELECT RELEASE_LOCK('spell_payment');"
        );
        if($_SERVER['REQUEST_METHOD'] == 'GET') {
            header("Location: " . $payment_redirect);
        }
    }

    /**
     * @param $payment_id
     * @return mixed|null
     */
    public function get_payment_data($payment_id)
    {
        return $this->spell_api()->get_payment($payment_id);
    }

    public function init_form_fields()
    {
        $form_fields_handler = new WC_Spell_Gateway_Payment_Form_Fields_Handler();

        $this->form_fields = $form_fields_handler->get_form_fields();
    }

    public function payment_fields()
    {
        if ($this->hid === 'no') {
            echo $this->method_description;
        } else {
            if (isset($this->global_mapping[$this->id])) {
                echo $this->payment_helper->render_payment_group($this->global_mapping[$this->id]);
            }
        }
    }

    public function get_button_image_url()
    {
        return $this->payment_helper->get_button_image_url();
    }

    public function process_payment($o_id)
    {
        global $woocommerce;
        $o = new WC_Order($o_id);
        $this->payment_helper->normalize_request($o);
        $total = round($o->calculate_totals() * 100);
        $spell = $this->spell_api();
        $u = home_url() . '/?wc-api=wc_gateway_klix&id=' . $o_id;

        $params = [
            'success_callback' => $u . "&action=paid",
            'success_redirect' => $u . "&action=paid",
            'failure_redirect' => $u . "&action=cancel",
            'cancel_redirect' => $u . "&action=cancel",
            'creator_agent' => 'Woocommerce v3 module: ' . SPELL_MODULE_VERSION,
            'reference' => (string)$o->get_order_number(),
            'platform' => 'woocommerce',
            'purchase' => [
                "currency" => $o->get_currency(),
                "language" => $this->payment_helper->get_language(),
                "notes" => $this->payment_helper->get_notes(),
                "products" => [
                    [
                        'name' => 'Payment',
                        'price' => $total,
                        'quantity' => 1,
                    ],
                ],
            ],
            'brand_id' => $this->shared_settings->get_option('brand-id'),
            'client' => [
                'email' => $o->get_billing_email(),
                'phone' => $o->get_billing_phone(),
                'full_name' => $o->get_billing_first_name() . ' ' . $o->get_billing_last_name(),
                'street_address' => $o->get_billing_address_1() . ' ' . $o->get_billing_address_2(),
                'country' => $o->get_billing_country(),
                'city' => $o->get_billing_city(),
                'zip_code' => $o->get_shipping_postcode(),
                'shipping_street_address' => $o->get_shipping_address_1() . ' ' . $o->get_shipping_address_2(),
                'shipping_country' => $o->get_shipping_country(),
                'shipping_city' => $o->get_shipping_city(),
                'shipping_zip_code' => $o->get_shipping_postcode(),
            ],
        ];

        $payment = $spell->create_payment($params);

        if (!array_key_exists('id', $payment)) {
            return array(
                'result' => 'failure',
            );
        }

        WC()->session->set(
            'spell_payment_id_' . $o_id,
            $payment['id']
        );

        $this->log_order_info('got checkout url, redirecting', $o);
        $u = $payment['checkout_url'];

        if (array_key_exists("spell-payment-method", $_REQUEST)) {
            $u .= "?preferred=" . $_REQUEST["spell-payment-method"];
        }

        return array(
            'result' => 'success',
            'redirect' => $u,
        );
    }

    /**
     * @param $products
     * @param false $process_single_product
     * @return array|string[]
     * @throws Exception
     */
    public function process_direct_payment($products, $process_single_product = false)
    {
        /**
         * This is a workaround for the "Pay Now" on PDP when the shopping cart might be empty.
         */
        if ($process_single_product) {
            // Clear the Cart
            WC()->cart->empty_cart();

            $product_id = $products[0]['product_id'];
            WC()->cart->add_to_cart($product_id);
        }

        $total = round(WC()->cart->get_cart_contents_total() * 100);
        if (WC()->cart->get_cart_discount_total() > 0) {
            $discount = round(WC()->cart->get_cart_discount_total() * 100);
            $total -= $discount;
        }

        $spell = $this->spell_api();
        $url = home_url() . '/?wc-api=wc_spell_callback';

        $params = [
            'success_callback' => $url . '&action=paid',
            'success_redirect' => $url . '&action=paid',
            'failure_redirect' => $url . '&action=cancel',
            'cancel_redirect' => $url . '&action=cancel',
            'creator_agent' => 'Woocommerce v3 module: ' . SPELL_MODULE_VERSION,
            'platform' => 'woocommerce',
            'client' => [
                'email' => 'dummy@data.com',
            ],
            'purchase' => [
                'notes' => $this->payment_helper->get_notes(),
                'products' => $products,
                'shipping_options' => $this->get_shipping_packages(),
            ],
            'total_override' => $total,
            'brand_id' => $this->shared_settings->get_option('brand-id'),
            'payment_method_whitelist' => ['klix']
        ];

        $directPayment = $spell->create_payment($params);
        if (!$directPayment || !array_key_exists('id', $directPayment)) {
            return array(
                'status' => 'failure',
            );
        }

        return array(
            'status' => 'success',
            'data' => $directPayment,
        );
    }
    public function get_shipping_packages()
    {
        $result = array();

        try {
            $shipping_packages = WC()->cart->get_shipping_packages();

            foreach ($shipping_packages as $package_id => $package) {
                if (WC()->session->__isset('shipping_for_package_' . $package_id)) {
                    /**
                     * @var $shipping_rate WC_Shipping_Rate
                     */
                    foreach (WC()->session->get('shipping_for_package_' . $package_id)['rates'] as $shipping_rate_id => $shipping_rate) {
                        $result[] = array(
                            'id' => $shipping_rate->get_id(),
                            'label' => $shipping_rate->get_label(),
                            'price' => round($shipping_rate->get_cost() * 100),
                        );
                    }
                }
            }
        } catch (Exception $e) {
            $this->spell_api()->log_error('Unable to retrieve shipping packages! Message - ' . $e->getMessage());
        }

        return $result;
    }

    public function can_refund_order($order)
    {
        $has_api_creds = $this->get_option('enabled') && $this->get_option('private-key') && $this->get_option('brand-id');

        return $order && $order->get_transaction_id() && $has_api_creds;
    }

    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (!$this->can_refund_order($order)) {
            $this->log_order_info('Cannot refund order', $order);
            return new WP_Error('error', __('Refund failed.', 'woocommerce'));
        }

        $spell = $this->spell_api();
        $params = [
            'amount' => round($amount * 100),
        ];

        $result = $spell->refund_payment($order->get_transaction_id(), $params);

        if (is_wp_error($result) || isset($result['__all__'])) {
            $this->spell_api()->log_error($result['__all__'] . ': ' . $order->get_order_number());

            return new WP_Error('error', var_export($result['__all__'], true));
        }

        $this->log_order_info('Refund Result: ' . wc_print_r($result, true), $order);

        switch (strtolower($result['status'])) {
            case 'success':
                $refund_amount = round($result['payment']['amount'] / 100, 2) . $result['payment']['currency'];

                $order->add_order_note(
                /* translators: 1: Refund amount, 2: Refund ID */
                    sprintf(__('Refunded %1$s - Refund ID: %2$s', 'woocommerce'), $refund_amount, $result['id'])
                );
                break;
            default:
                $this->log_order_info('Refund result status is missing: ' . wc_print_r($result, true), $order);
                break;
        }

        return true;
    }

    public function is_available()
    {
        $result = parent::is_available();

        if (!$result) {
            $result = ('yes' === $this->shared_settings->get_option('enabled'));
        }

        return $result;
    }
}
