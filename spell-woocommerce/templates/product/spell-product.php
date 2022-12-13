<?php

defined( 'ABSPATH' ) || exit;

require_once dirname(__FILE__) . '/../../spell-woocommerce.php';


add_action('wp_head', 'add_pay_later_widget_script_to_head');
add_action('woocommerce_after_add_to_cart_button', 'add_pay_later_widget_in_product');
add_action('woocommerce_after_add_to_cart_button', 'add_direct_payment_button_in_product');

function add_direct_payment_button_in_product()
{
    class Spell_Product
    {
        public function __construct()
        {
            $this->spellPayment = new WC_Spell_Gateway();
        }

        public function init_button()
        {
            global $product;

            $is_in_stock = method_exists( $product, 'is_in_stock' ) === true ? $product->is_in_stock() : $product->is_in_stock;
            $product_id = method_exists( $product, 'get_id' ) === true ? $product->get_id() : $product->id;
            $enabled = $this->spellPayment->get_option('enabled') === 'yes' ? true : false;
            $direct_payment = $this->spellPayment->get_option('direct_payment_enabled') === 'yes' ? true : false;
            $direct_payment_text = $this->spellPayment->get_option('direct_payment_text');
            $direct_payment_styles = $this->spellPayment->get_option('direct_payment_styles_pdp');
            $is_direct_payment_enabled = $enabled && $direct_payment;
            $image_url = $this->spellPayment->get_button_image_url();

            if ($is_in_stock && $product_id && $is_direct_payment_enabled) {
                $button_html = '<div id="direct-payment-wrapper"><button type="submit" name="add-to-cart-direct" value="1" class="direct-payment-button product-page button alt"><img src="'. $image_url . '"></button></div>';

                if ($direct_payment_styles !== '') {
                    $button_html = '<style>'.$direct_payment_styles.'</style>'.$button_html;
                }

                // Add hidden input for simple and external products for the direct payment button to function correctly
                if (!in_array($product->get_type(), array('variable', 'grouped'))) {
                    $button_html .= '<input type="hidden" name="add-to-cart" value="'.$product_id.'"/>';
                }

                return $button_html;
            }

            return '';
        }
    }

    $GLOBALS['wc_spell_product'] = new Spell_Product();
    echo $GLOBALS['wc_spell_product']->init_button();
}

function add_pay_later_widget_in_product()
{
    class Pay_Later_Widget
    {
        public function __construct()
        {
            $this->spellPayment = new WC_Spell_Gateway();
        }

        public function init_widget()
        {
            global $product;
            
            
            $API_enabled = $this->spellPayment->get_option('enabled') === 'yes' ? true : false;

            $product_price=wc_get_price_to_display($product);

            $product_price=round($product_price,2)*100;

            $language=substr(get_locale(), 0, 2);
            $brand_id=$this->spellPayment->get_option('brand-id');

            if ($API_enabled && $brand_id!=='') {

                $widget_html = sprintf('<klix-pay-later amount="%d" brand_id="%s" 
                language="%s" theme="light" view="product">
                </klix-pay-later>',$product_price,$brand_id,$language);

                return $widget_html;
            }
            return '';
        }
    }

    $GLOBALS['wc_spell_product'] = new Pay_Later_Widget();
    echo $GLOBALS['wc_spell_product']->init_widget();
}

function add_pay_later_widget_script_to_head()
{
    ?>
   <script type="module" src="https://klix.blob.core.windows.net/public/pay-later-widget/build/klix-pay-later-widget.esm.js"></script>
   <script nomodule="" src="https://klix.blob.core.windows.net/public/pay-later-widget/build/klix-pay-later-widget.js"></script>
    <?php
}