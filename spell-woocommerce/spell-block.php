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
    $script_path = 'build/index.js';
    $script_url  = plugin_dir_url( __FILE__ ) . $script_path;
    
    $asset_file = plugin_dir_path( __FILE__ ) . 'build/index.asset.php';
    $asset_data = file_exists( $asset_file ) 
        ? include $asset_file 
        : array( 'dependencies' => array(), 'version' => '1.0.1' );

    wp_register_script(
        'klix-blocks-integration',
        $script_url,
        $asset_data['dependencies'], 
        $asset_data['version'],      
        true
    );

    if ( function_exists( 'wp_set_script_translations' ) ) {
        wp_set_script_translations(
            'klix-blocks-integration',
            'klix-payments',
            plugin_dir_path( __FILE__ ) . 'languages'
        );
    }

    $payment_method_data = $this->get_payment_method_data();
    wp_localize_script( 'klix-blocks-integration', 'klixPaymentData', $payment_method_data );

    return array( 'klix-blocks-integration' );
}

    public function get_payment_method_data() {
        $shared_settings = new WC_Spell_Gateway_Payment_Settings();
        $spell_api = new WC_Spell_Gateway_Payment_Api();
        $payment_helper = new WC_Spell_Gateway_Payment_Helper();
        
        $payment_methods = [];
        if ($shared_settings->get_option('hid') === 'yes' && isset(WC()->cart->total)) {
            $amount = WC()->cart->total*100;
            $payment_methods = $spell_api->spell_api()->payment_methods(
                get_woocommerce_currency(),
                $payment_helper->get_language(),
                $amount
            );
        }

    return [
        'title' => __($shared_settings->get_option('label'), 'klix-payments'),
        'description' => __($shared_settings->get_option('method_desc'), 'klix-payments'),
        'supports' => [ 'products','wc_blocks_checkout'],
        'payment_methods' => $payment_methods,
        'desired_payment_method_order' => json_decode($shared_settings->get_option('method_order'),true),
        'desired_multilink_method_order' => json_decode($shared_settings->get_option('multilink_method_order'),true)
    ];
}
}
?>