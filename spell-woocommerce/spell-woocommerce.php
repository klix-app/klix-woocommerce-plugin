<?php

/**
 * Plugin Name: Klix E-commerce Gateway
 * Plugin URI:
 * Description: Klix E-commerce Gateway
 * Version: 1.2.4
 * Author: Klix
 * Author URI:
 * Developer: Klix
 * Developer URI:
 *
 * Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
 * WC requires at least: 3.3.4
 * WC tested up to: 4.0.0
 *
 * Copyright: © 2020 Klix
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// based on
// http://docs.woothemes.com/document/woocommerce-payment-gateway-plugin-base/
// docs http://docs.woothemes.com/document/payment-gateway-api/

require_once dirname(__FILE__) . '/api.php';

class WC_Spell
{
    public function __construct()
    {
        add_action('init', array($this, 'init_helper_classes'), 15);
        add_action('init', array($this, 'init_gateway_classes'), 15);
        add_action('init', array($this, 'include_template_functions'), 20);
        add_action('init', array($this, 'wc_session_enabler'), 25);
        add_action('wp_enqueue_scripts', array($this, 'wc_spell_load_css'));
        add_action('plugins_loaded', 'wc_spell_payment_gateway_init');
    }

    public function include_template_functions()
    {
        include('templates/product/spell-product.php');
        include('templates/product/spell-product-controller.php');
        include('templates/cart/spell-cart.php');
        include('templates/cart/spell-cart-controller.php');
        include('templates/checkout/spell-checkout.php');
        include('templates/checkout/spell-callback-controller.php');
    }

    public function init_helper_classes()
    {
        include_once('logger/wc-spell-logger.php');
        include_once('helper/wc-spell-gateway-payment-form-fields-handler.php');
        include_once('helper/wc-spell-gateway-payment-helper.php');
        include_once('helper/wc-spell-gateway-payment-settings.php');
        include_once('helper/wc-spell-gateway-payment-api.php');
        include_once('helper/wc-spell-gateway-payment-methods-mapper.php');
    }

    public function init_gateway_classes()
    {
        include_once('gateway/wc-spell-gateway-abstract.php');
        include_once('gateway/wc-spell-gateway.php');
        include_once('gateway/wc-spell-gateway-klix.php');
        include_once('gateway/wc-spell-gateway-klix-pay-later.php');
        include_once('gateway/wc-spell-gateway-klix-card.php');
        include_once('gateway/wc-spell-gateway-bank-transfer.php');
    }

    // Set customer session to correctly retrieve all available shipping methods later
    public function wc_session_enabler()
    {
        if (is_user_logged_in() || is_admin()) {
            return;
        }

        if (isset(WC()->session) && !WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }
    }

    function wc_spell_load_css()
    {
        wp_enqueue_style('spell-style', plugin_dir_url(__FILE__) . 'assets/css/spell.css');
    }
}

$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

/**
 * Check if the WC plugin is activated
 */
if (in_array('woocommerce/woocommerce.php', $active_plugins)) {
    new WC_Spell();
}

function wc_spell_payment_gateway_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Add the Gateway to WooCommerce
    function woocommerce_add_spell_gateway($methods)
    {
        $shared_settings = new WC_Spell_Gateway_Payment_Settings();
        $payment_helper = new WC_Spell_Gateway_Payment_Helper();
        $spell_api = new WC_Spell_Gateway_Payment_Api();
        $methods[] = WC_Spell_Gateway::class;

        /**
         * Hide redundant settings in the admin panel
         */
        if (function_exists('get_current_screen') && get_current_screen() !== null && get_current_screen()->id === 'woocommerce_page_wc-settings') {
            return $methods;
        }

        if (!function_exists('is_checkout')) {
            require_once '/includes/wc-conditional-functions.php';
        }

        $is_checkout_page = is_checkout();

        /**
         * We need to send API requests only when in the checkout
         */
        if (!$is_checkout_page) {
            return $methods;
        }

        if ($shared_settings->get_option('hid') === 'yes') {
            $payment_methods = $spell_api->spell_api()->payment_methods(
                get_woocommerce_currency(),
                $payment_helper->get_language()
            );

            if (is_null($payment_methods)) {
                echo('System error!');
                return;
            }

            if (!array_key_exists("by_country", $payment_methods)) {
                echo 'Plugin configuration error!';
                return;
            }

            $payment_groups_mapper = new WC_Spell_Gateway_Payment_Methods_Mapper($payment_methods);
            $payment_groups = $payment_groups_mapper->get_payment_groups();
            $GLOBALS['spell_payment_groups'] = $payment_groups;

            $payment_groups_map = [
                'klix' => WC_Spell_Gateway_Klix::class,
                'klix_pay_later' => WC_Spell_Gateway_Klix_Pay_Later::class,
                'klix_card' => WC_Spell_Gateway_Klix_Card::class,
                'bank_transfer' => WC_Spell_Gateway_Bank_Transfer::class,
            ];

            foreach ($payment_groups as $payment_group) {
                if (array_key_exists($payment_group['id'], $payment_groups_map)) {
                    $methods[] = $payment_groups_map[$payment_group['id']];
                }
            }
        }

        return $methods;
    }

    /**
	 * By default, new payment gateways are put at the bottom of the list on the admin "Payments" settings screen.
	 *
	 * @param array $ordering Existing ordering of the payment gateways.
	 *
	 * @return array Modified ordering.
	 */
	function set_gateway_in_sorting_list( $ordering ) {
		$ordering                   = (array) $ordering;
		$ordering['klix']           = $ordering['spell'];
		$ordering['bank_transfer']  = $ordering['spell'];
		$ordering['klix_card']      = $ordering['spell'];
		$ordering['klix_pay_later'] = $ordering['spell'];


        asort( $ordering );
        $i = 0;
        foreach ( $ordering as $key => $order ) {
            $ordering[ $key ] = $i;
            $i++;
        }

		return $ordering;
	}

	add_filter( 'option_woocommerce_gateway_order', 'set_gateway_in_sorting_list' );
	add_filter( 'default_option_woocommerce_gateway_order', 'set_gateway_in_sorting_list' );
	add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_spell_gateway' );

    function wp_add_spell_setting_link($links)
    {
        $url = get_admin_url()
            . '/admin.php?page=wc-settings&tab=checkout&section=spell';
        $settings_link = '<a href="' . $url . '">' . __('Settings', 'spell')
            . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    add_filter(
        'plugin_action_links_' . plugin_basename(__FILE__),
        'wp_add_spell_setting_link'
    );

    /**
     * Disable the default "spell" payment method on the frontend
     *
     * @param $available_gateways
     * @return mixed
     */
    function disable_spell_on_the_frontend($available_gateways)
    {
        $shared_settings = new WC_Spell_Gateway_Payment_Settings();

        /**
         * Fallback to the default settings.
         */
        if ($shared_settings->get_option('hid') === 'no') {
            return $available_gateways;
        }

        if (isset($available_gateways['spell'])) {
            unset($available_gateways['spell']);
        }

        return $available_gateways;
    }

    add_filter('woocommerce_available_payment_gateways', 'disable_spell_on_the_frontend');
}
