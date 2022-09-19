<?php

defined('ABSPATH') || exit;

require_once dirname(__FILE__) . '/../../spell-woocommerce.php';

add_action('woocommerce_api_wc_spell_callback', 'handle_spell_callback');
add_action( 'woocommerce_thankyou', 'custom_woocommerce_auto_complete_order' );

function handle_spell_callback()
{
    class Spell_Callback_Controller
    {
        public function __construct()
        {
            $this->spellPayment = new WC_Spell_Gateway();
            $this->create_order_from_return_data();
        }

        public function create_order_from_return_data()
        {
            global $woocommerce;
            // If order was paid for, then create order
            if ($_GET['action'] === 'paid') {
                $GLOBALS['wpdb']->get_results(
                    "SELECT GET_LOCK('spell_payment', 15);"
                );

                $payment_id = WC()->session->get('spell_direct_payment_id');

                if (!$payment_id) {
                    $input = json_decode(file_get_contents('php://input'), true);
                    $payment_id = array_key_exists('id', $input) ? $input['id'] : '';
                }

                /**
                 * First, try loading the order via transaction id, otherwise create a new order.
                 */
                $orders = wc_get_orders(array('transaction_id' => $payment_id));
                $order = count($orders) ? $orders[0] : wc_create_order();

                if ($this->spell_api()->was_payment_successful($payment_id)) {
                    /**
                     * Process order only if it's not paid yet
                     */
                    if (!$order->is_paid()) {
                        $payment_data = $this->spellPayment->get_payment_data($payment_id);
                        $orderData = $payment_data['client'];
                        $this->add_products_from_order_data($order, $payment_data['purchase']['products']);
                        $this->add_shipping_item_from_order_data($order, $orderData['shipping_street_address'], $payment_data['purchase']['shipping_options']);

                        $order->set_address($this->get_address_from_order_data($orderData),'billing');
                        $order->set_address($this->get_address_from_order_data($orderData),'shipping');
                        $order->set_payment_method($this->spellPayment);
                        $order->set_transaction_id($payment_id);
                        $order->set_date_paid(time());
                        $order->calculate_totals();

                        /**
                         * Update the order status to "complete" and add the purchase notes from the response.
                         */
                        $order->update_status('completed', $payment_data['purchase']['notes']);

                        $this->log_order_info('direct payment processed', $order);
                    }

                    WC()->cart->empty_cart();
                } else {
                    $message = sprintf(
                        __('Direct payment was not successful. Transaction ID: %s', 'woocommerce'),
                        $payment_id
                    );

                    $this->spell_api()->log_info($message);
                }

                $GLOBALS['wpdb']->get_results(
                    "SELECT RELEASE_LOCK('spell_payment');"
                );

                wp_redirect($this->spellPayment->get_return_url($order));
            } else {
                $cancel_redirect = $woocommerce->cart->get_cart_url();
                wp_redirect($cancel_redirect);
            }
        }

        public function get_address_from_order_data($orderData)
        {
            return array(
                'first_name' => $orderData['full_name'], // @todo: Split into 'first_name' and 'last_name' if necessary
                'last_name'  => '',
                'company'    => '',
                'email'      => $orderData['email'],
                'phone'      => $orderData['phone'],
                'address_1'  => $orderData['shipping_street_address'],
                'address_2'  => '',
                'city'       => $orderData['shipping_city'],
                'state'      => '',
                'postcode'   => $orderData['shipping_zip_code'],
                'country'    => $orderData['shipping_country']
            );
        }

        public function add_products_from_order_data($order, $products)
        {
            foreach ($products as $product_data) {
                $product_id = $this->get_product_id_from_response($product_data['name']);
                $product = wc_get_product($product_id);
                $order->add_product($product, (int)$product_data['quantity']);
            }
        }

        public function add_shipping_item_from_order_data($order, $shipping_address, $shipping_options)
        {
            $selected_shipping_method_id = $this->get_shipping_method_id_from_street_address($shipping_address);

            foreach ($shipping_options as $shipping_option) {
                if ($shipping_option['id'] === $selected_shipping_method_id) {
                    // Get a new instance of the WC_Order_Item_Shipping Object
                    $item = new WC_Order_Item_Shipping();
                    $price = round($shipping_option['price'] / 100, 2);

                    $item->set_method_title($shipping_option['label']);
                    $item->set_method_id($shipping_option['id']);
                    $item->set_total($price);

                    $order->add_item($item);
                }
            }
        }

        /**
         * We will always get it as a first element before ","
         *
         * @param $street_address
         * @return mixed|string
         */
        private function get_shipping_method_id_from_street_address($street_address)
        {
            $array = explode(",", $street_address);

            return $array[0];
        }

        /**
         * The order "name" attribute in the response contains both product_id & product name values
         * Product ID is separated by comma (",")
         *
         * @param $name
         * @return mixed
         */
        private function get_product_id_from_response($name)
        {
            $result = explode(",", $name);

            return $result[0];
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
    }

    new Spell_Callback_Controller();
}

function custom_woocommerce_auto_complete_order( $order_id ) {
    
    $order = wc_get_order( $order_id );
    
    $purchase_id=WC()->session->get(
        'spell_payment_id_' . $order_id
    );
    $payment_gateways=["bank_transfer","klix_card","klix_pay_later"];
    
    if($purchase_id === "" or !in_array($order->payment_method, $payment_gateways)){
        return;
    }

    $klix_gateway=new WC_Spell_Gateway_Payment_Api();
    $payment_successful=$klix_gateway->spell_api()->was_payment_successful($purchase_id);

    $spellPayment = new WC_Spell_Gateway();
    $orderStatus = $spellPayment->get_option('order-status-after-payment');

    if( $payment_successful && ($order->status !== "completed" &&  $orderStatus === "completed")) {    
        $order->update_status( $orderStatus );
    }
}