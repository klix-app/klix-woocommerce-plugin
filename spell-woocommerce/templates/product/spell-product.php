<?php

defined( 'ABSPATH' ) || exit;

require_once dirname(__FILE__) . '/../../spell-woocommerce.php';

class Pay_Later_Widget_Spell {

    private $spellPayment;

    public function __construct() {
        $this->spellPayment = new WC_Spell_Gateway();
        add_action('woocommerce_after_add_to_cart_form', [ $this, 'render_widget' ]);
        add_action('wp_head', [ $this, 'inject_scripts' ], 1);
    }

    private function should_load_scripts() {
        return is_product() || is_checkout();
    }

    public function inject_scripts() {
        if ( ! $this->should_load_scripts() ) {
            return;
        }

        ?>
        <script type="module" src="https://klix.blob.core.windows.net/public/pay-later-widget/build/klix-pay-later-widget.esm.js"></script>
        <script nomodule src="https://klix.blob.core.windows.net/public/pay-later-widget/build/klix-pay-later-widget.js"></script>
        <?php
    }

    public function render_widget() {
        global $product;

        if ( ! $product ) {
            return;
        }

        $enabled   = $this->spellPayment->get_option('enabled') === 'yes';
        $brand_id  = sanitize_text_field($this->spellPayment->get_option('brand-id'));

        if ( ! $enabled || empty($brand_id) ) {
            return;
        }

        // Product price
        $price = wc_get_price_to_display($product);
        $amount = round(floatval($price), 2) * 100;

        $language = substr(get_locale(), 0, 2);

        echo sprintf(
            '<div class="klix-widget-wrapper"><klix-pay-later amount="%d" brand_id="%s" language="%s" theme="light" view="product"></klix-pay-later></div>',
            intval($amount),
            esc_attr($brand_id),
            esc_attr($language)
        );
    }
}

new Pay_Later_Widget_Spell();

add_action('wp_footer', function () {
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            if (!window.customElements || !customElements.whenDefined) return;

            customElements.whenDefined('klix-pay-later').then(() => {
                document.querySelectorAll('klix-pay-later').forEach(el => {
                    if (typeof el.forceUpdate === 'function') {
                        el.forceUpdate();
                    }
                });
            });
        });
    </script>
    <?php
});
