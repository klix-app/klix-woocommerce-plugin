<?php

/**
 * A separate class to share the module settings across different payment methods.
 */
class WC_Spell_Gateway_Payment_Form_Fields_Handler
{
    public function get_form_fields()
    {
        return array(
            'enabled' => array(
                'title' => __('Enable API', 'woocommerce'),
                'label' => __('Enable API', 'woocommerce'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'hid' => array(
                'title' => __('Enable payment method selection', 'woocommerce'),
                'label' => __('Enable payment method selection', 'woocommerce'),
                'type' => 'checkbox',
                'description' => 'If set, buyers will be able to choose the desired payment method directly in WooCommerce',
                'default' => 'yes',

            ),
            'method_desc' => array(
                'title' => __('Change payment method description', 'woocommerce'),
                'label' => __('', 'woocommerce'),
                'type' => 'text',
                'description' => 'If not set, "Choose payment method on next page" will be used',
                'default' => 'Choose payment method on next page',
            ),
            'label' => array(
                'title' => __('Change payment method title', 'woocommerce'),
                'type' => 'text',
                'description' => 'If not set, "Select payment method" will be used. Ignored if payment method selection is enabled',
                'default' => 'Select Payment Method',
            ),
            'brand-id' => array(
                'title' => __('Brand ID', 'woocommerce-spell'),
                'type' => 'text',
                'description' => __(
                    'Please enter your brand ID',
                    'woocommerce-spell'
                ),
                'default' => '',
            ),
            'private-key' => array(
                'title' => __('Secret key', 'woocommerce-spell'),
                'type' => 'text',
                'description' => __(
                    'Please enter your secret key',
                    'woocommerce-spell'
                ),
                'default' => '',
            ),
            'debug' => array(
                'title' => __('Debug Log', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'woocommerce'),
                'default' => 'yes',
                'description' =>
                    sprintf(
                        __(
                            'Log events to <code>%s</code>',
                            'woocommerce'
                        ),
                        wc_get_log_file_path('spell')
                    ),
            ),
            array(
                'title' => __('Direct payment options', 'woocommerce'),
                'type' => 'title',
                'desc' => '',
                'id' => 'direct_payment_options',
            ),
            'direct_payment_enabled' => array(
                'title' => __('Enable Directed Payment', 'woocommerce'),
                'label' => __('Enable Directed Payment', 'woocommerce'),
                'type' => 'checkbox',
                'description' => 'If set, buyers will be able to directly purchase products from the product/cart page',
                'default' => 'no',
                'checkboxgroup' => 'start',
                'show_if_checked' => 'option',
            ),
            'direct_payment_text' => array(
                'title' => __('Direct payment button text', 'woocommerce'),
                'label' => __('', 'woocommerce'),
                'type' => 'text',
                'description' => '',
                'default' => 'Express checkout',
                'checkboxgroup' => '',
                'show_if_checked' => 'yes',
            ),
            'direct_payment_styles_pdp' => array(
                'title' => __('Direct payment button styles in product page', 'woocommerce'),
                'label' => __('', 'woocommerce'),
                'type' => 'textarea',
                'description' => '',
                'default' => '',
                'css' => 'height:150px;',
                'checkboxgroup' => '',
                'show_if_checked' => 'yes',
            ),
            'direct_payment_styles_cart' => array(
                'title' => __('Direct payment button styles in cart', 'woocommerce'),
                'label' => __('', 'woocommerce'),
                'type' => 'textarea',
                'description' => '',
                'default' => '',
                'css' => 'height:150px;',
                'checkboxgroup' => '',
                'show_if_checked' => 'yes',
            ),
            'direct_payment_styles_checkout' => array(
                'title' => __('Direct payment button styles in checkout', 'woocommerce'),
                'label' => __('', 'woocommerce'),
                'type' => 'textarea',
                'description' => '',
                'default' => '',
                'css' => 'height:150px;',
                'checkboxgroup' => 'end',
                'show_if_checked' => 'yes',
            ),
        );
    }
}
