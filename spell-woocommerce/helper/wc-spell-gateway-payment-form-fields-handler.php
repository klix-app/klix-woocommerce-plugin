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
            'order-status-after-payment' => array(
                'title' => __('Order status', 'woocommerce-spell'),
                'type' => 'select',
                'description' => __(
                    'Specify to which status, order should change after successful payment',
                    'woocommerce-spell'
                ),
                'options'=>[
                    'processing'=>'Processing',
                    'completed'=>'Completed'
                ],
                'default' => 'processing',
            ),

            
            array(
                'title' => __('Direct payment options', 'woocommerce'),
                'type' => 'title',
                'desc' => '',
                'id' => 'direct_payment_options',
            ),
            'hid' => array(
                'title' => __('Enable payment method selection', 'woocommerce'),
                'label' => __('Enable payment method selection', 'woocommerce'),
                'type' => 'checkbox',
                'description' => 'If set, buyers will be able to choose the desired payment method directly in WooCommerce',
                'default' => 'yes',
            ),

            'label' => array(
                'title' => __('Change payment method title', 'woocommerce'),
                'type' => 'text',
                'description' => 'If not set, "Select payment method" will be used. Ignored if payment method selection is enabled',
                'default' => 'Select Payment Method',
            ),
            'method_desc' => array(
                'title' => __('Change payment method description', 'woocommerce'),
                'label' => __('', 'woocommerce'),
                'type' => 'text',
                'description' => 'If not set, "Choose payment method on next page" will be used',
                'default' => 'Choose payment method on next page',
            ),
            'hide_pay_later' => array(
                'title' => __('Hide Pay Later payment option under specified amount', 'woocommerce'),
                'label' => __('', 'woocommerce'),
                'type' => 'checkbox',
                'description' => 'If set, Pay Later payment method will be hidden under specified amount',
                'default' => 'no'
            ),
            'hide_pay_later_amount' => array(
                'title' => __('Minimal amount for Pay Later', 'woocommerce'),
                'label' => __('', 'woocommerce'),
                'type' => 'number',
                'description' => '',
                'default' => '50',
                'min'=>0
            ),
            
            'payment_methods_styles' => array(
                'title' => __('Payment methods styles', 'woocommerce'),
                'label' => __('', 'woocommerce'),
                'type' => 'textarea',
                'description' => '',
                'default' => '
                /* Common styles for various payment methods */
li.payment_method_bank_transfer .spell-pm-image img {
    margin-left: 0 !important;
    margin-top: 25px !important;
    max-height: none !important;
    height: 28px !important;
    width: auto !important;
    float: left !important;
}

li.payment_method_klix img,
li.payment_method_bank_transfer img,
li.payment_method_klix_pay_later img,
li.payment_method_klix_card img {
    max-height: 41px !important;
    display: block;
    float: right !important;
}

li.payment_method_klix:nth-child(1),
li.payment_method_bank_transfer:nth-child(1),
li.payment_method_klix_pay_later:nth-child(1),
li.payment_method_klix_card:nth-child(1) {
    margin-top: 20px !important;
}

li.payment_method_bank_transfer .spell--pm-wrapper {
    display: flex;
    align-items: center;
}

li.payment_method_bank_transfer .spell--pm-wrapper span {
    margin-left: 15px;
}

@media (min-width: 768px) {
    li.payment_method_bank_transfer .spell-pm-image img {
 
        height: auto;
    }
}

@media (max-width: 767px) {
    li.payment_method_bank_transfer .spell-pm-image img {
     
        height: 30px !important;
        margin-left: 15px !important;
    }
}

/* Hide images under #wc-payment for bank transfer method */
#wc-payment .wc_payment_method_bank_transfer label img {
    display: none !important;
}
                ',
                'css' => 'height:150px;'
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
        );
    }
}
