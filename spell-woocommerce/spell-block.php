<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Klix_Gateway_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'klix-payments';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_klix-payments_settings', [] );
    }

    public function is_active() {
        return true;
    }

    public function get_payment_method_script_handles() {

        wp_register_script(
            'klix-blocks-integration',
            plugin_dir_url(__FILE__) . 'checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        $payment_method_data = $this->get_payment_method_data();
        wp_localize_script('klix-blocks-integration', 'klixPaymentData', $payment_method_data);

        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'klix-blocks-integration');
            
        }
        return [ 'klix-blocks-integration' ];
    }

    public function get_payment_method_data() {
        $shared_settings = new WC_Spell_Gateway_Payment_Settings();

        return [
            'title' => __($shared_settings->get_option('label'), 'klix-payments'),
            'description' => __($shared_settings->get_option('method_desc'), 'klix-payments')
        ];
    }

    

}
?>