<?php

/**
 * A separate class to share the module settings across different payment methods.
 */
class WC_Spell_Gateway_Payment_Form_Fields_Handler
{
    public function get_form_fields()
    {
        $log_handler = new WC_Log_Handler_File();
        $log_file_path = $log_handler->get_log_file_path('klix-payments');
        return array(
            'enabled' => array(
                'title' => __('Enable API', 'klix-payments'),
                'label' => __('Enable API', 'klix-payments'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'brand-id' => array(
                'title' => __('Brand ID', 'klix-payments'),
                'type' => 'text',
                'description' => __(
                    'Please enter your brand ID',
                    'klix-payments'
                ),
                'default' => '',
            ),
            'private-key' => array(
                'title' => __('Secret key', 'klix-payments'),
                'type' => 'text',
                'description' => __(
                    'Please enter your secret key',
                    'klix-payments'
                ),
                'default' => '',
            ),
            'order-status-after-payment' => array(
                'title' => __('Order status', 'klix-payments'),
                'type' => 'select',
                'description' => __(
                    'Specify to which status, order should change after successful payment',
                    'klix-payments'
                ),
                'options'=>[
                    'processing'=>'Processing',
                    'completed'=>'Completed'
                ],
                'default' => 'processing',
            ),
            'hid' => array(
                'title' => __('Enable payment method selection', 'klix-payments'),
                'label' => __('Enable payment method selection', 'klix-payments'),
                'type' => 'checkbox',
                'description' => 'If set, buyers will be able to choose the desired payment method directly in WooCommerce',
                'default' => 'yes',
            ),

            'label' => array(
                'title' => __('Change payment method title', 'klix-payments'),
                'type' => 'text',
                'description' => 'If not set, "Select payment method" will be used. Ignored if payment method selection is enabled',
                'default' => 'Select Payment Method',
            ),
            'method_desc' => array(
                'title' => __('Change payment method description', 'klix-payments'),
                'label' => __('', 'klix-payments'),
                'type' => 'text',
                'description' => 'If not set, "Choose payment method on next page" will be used',
                'default' => 'Choose payment method on next page',
            ),
            'hide_pay_later' => array(
                'title' => __('Hide Pay Later payment option under specified amount', 'klix-payments'),
                'label' => __('', 'klix-payments'),
                'type' => 'checkbox',
                'description' => 'If set, Pay Later payment method will be hidden under specified amount',
                'default' => 'no'
            ),
            'hide_pay_later_amount' => array(
                'title' => __('Minimal amount for Pay Later', 'klix-payments'),
                'label' => __('', 'klix-payments'),
                'type' => 'number',
                'description' => '',
                'default' => '50',
                'min'=>0
            ),
            
            'payment_methods_styles' => array(
                'title' => __('Payment methods styles', 'klix-payments'),
                'label' => __('', 'klix-payments'),
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
                'title' => __('Debug Log', 'klix-payments'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'klix-payments'),
                'default' => 'yes',
                'description' =>
                    sprintf(
                        __(
                            'Log events to <code>%s</code>',
                            'woocommerce'
                        ),
                        $log_file_path
                    ),
            ),
        );
    }
}
