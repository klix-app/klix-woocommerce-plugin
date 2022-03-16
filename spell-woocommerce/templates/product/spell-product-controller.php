<?php

defined('ABSPATH') || exit;

require_once dirname(__FILE__) . '/../../spell-woocommerce.php';

add_action('woocommerce_add_to_cart', 'redirect_to_spell_checkout_from_product');
function redirect_to_spell_checkout_from_product()
{
    if (!class_exists('Spell_Product_Controller')) {
        // If 'add-to-cart-direct' is set, it means the direct payment button has been pressed
        if (isset($_REQUEST['add-to-cart-direct']) && $_REQUEST['quantity']) {
            class Spell_Product_Controller
            {
                public function __construct()
                {
                    $this->spellPayment = new WC_Spell_Gateway();
                    $this->redirect();
                }

                public function redirect()
                {
                    if (!isset($_REQUEST['variation_id'])) {
                        $request_product_id = $_REQUEST['add-to-cart'];
                    } else {
                        $request_product_id = $_REQUEST['variation_id'];
                    }

                    $products = array();

                    if (is_array($_REQUEST['quantity'])) {
                        foreach ($_REQUEST['quantity'] as $product_id => $quantity) {
                            if ($quantity) {
                                $product = wc_get_product($product_id);
                                $name = method_exists($product, 'get_name') === true ? $product->get_name() : $product->name;
                                $price = method_exists($product, 'get_price') === true ? $product->get_price() : $product->price;
                                $products[] = array(
                                    'product_id' => $product_id,
                                    'name' => $product_id . ',' . $name,
                                    'price' => round($price * 100),
                                    'quantity' => $quantity
                                );
                            }
                        }
                    } else {
                        $product = wc_get_product($request_product_id);
                        $name = method_exists($product, 'get_name') === true ? $product->get_name() : $product->name;
                        $price = method_exists($product, 'get_price') === true ? $product->get_price() : $product->price;
                        $products[] = array(
                            'product_id' => $request_product_id,
                            'name' => $request_product_id . ',' . $name,
                            'price' => round($price * 100),
                            'quantity' => $_REQUEST['quantity']
                        );
                    }

                    $result = $this->spellPayment->process_direct_payment($products, true);

                    if ($result['status'] !== 'failure' && $result['data']['checkout_url']) {
                        WC()->session->set('spell_direct_payment_id', $result['data']['id']);
                        wp_redirect($result['data']['checkout_url']);
                    } else {
                        wp_redirect($_SERVER['HTTP_REFERER']);
                    }

                    /**
                     * this is required to prevent the default "Add to cart" functionality
                     */
                    exit;
                }
            }

            new Spell_Product_Controller();
        }
    }
}
