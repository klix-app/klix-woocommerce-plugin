<?php

defined( 'ABSPATH' ) || exit;

require_once dirname(__FILE__) . '/../../spell-woocommerce.php';


add_action('wp_head', 'add_pay_later_widget_script_to_head');
add_action('woocommerce_after_add_to_cart_button', 'add_pay_later_widget_in_product');

class Pay_Later_Widget_Spell
{
    private $spellPayment;

    public function __construct()
    {
        $this->spellPayment = new WC_Spell_Gateway();
    }

    public function init_widget()
    {
        global $product;

        $API_enabled = $this->spellPayment->get_option('enabled') === 'yes';
        $product_price = wc_get_price_to_display($product);
        $product_price = round($product_price, 2) * 100;
        $language = substr(get_locale(), 0, 2);
        $brand_id = $this->spellPayment->get_option('brand-id');

        if ($API_enabled && $brand_id !== '') {
            $widget_html = sprintf(
                '<klix-pay-later amount="%d" brand_id="%s" language="%s" theme="light" view="product"></klix-pay-later>',
                $product_price,
                $brand_id,
                $language
            );

            return $widget_html;
        }

        return '';
    }
}


function add_pay_later_widget_in_product()
{
    $payLaterWidget = new Pay_Later_Widget_Spell();
    echo $payLaterWidget->init_widget();
}

function add_pay_later_widget_script_to_head()
{
    ?>
    <script type="module" src="https://klix.blob.core.windows.net/public/pay-later-widget/build/klix-pay-later-widget.esm.js"></script>
    <script nomodule="" src="https://klix.blob.core.windows.net/public/pay-later-widget/build/klix-pay-later-widget.js"></script>
    <?php
}