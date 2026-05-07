<?php

class WC_Spell_Gateway_Payment_Helper
{
    /**
     * @return string
     */
    public function get_language()
    {
        if (defined('ICL_LANGUAGE_CODE')) {
            $ln = ICL_LANGUAGE_CODE;
        } else {
            $ln = get_locale();
        }
        switch ($ln) {
            case 'et_EE':
                $ln = 'et';
                break;
            case 'ru_RU':
                $ln = 'ru';
                break;
            case 'lt_LT':
                $ln = 'lt';
                break;
            case 'lv_LV':
                $ln = 'lv';
                break;
            case 'et':
            case 'lt':
            case 'lv':
            case 'ru':
                break;
            default:
                $ln = 'en';
        }

        return $ln;
    }

    public function render_payment_group($payment_group)
    {
        $result = '';
        $result .= "<div style=\"display: flex; flex-flow: row wrap;\">";
        $result .= $this->get_payment_methods_html($payment_group['id'], $payment_group['methods'],$payment_group['label']);
        $result .= "</div>";

        return $result;
    }

    function sort_multilink_methods_by_order(array $methods, array $order): array
    {
        $sorted = [];
        $remaining = $methods;

        foreach ($order as $key) {
            $pattern = '/^' . preg_quote($key, '/') . '_(lv|ee|lt)_pis$/';

            foreach ($remaining as $i => $method) {
                if (preg_match($pattern, $method['id'])) {
                    $sorted[] = $method;
                    unset($remaining[$i]);
                }
            }
        }
        
        return array_merge($sorted, array_values($remaining));
    }



    /**
     * @param $payment_methods
     * @return string
     */
    private function get_payment_methods_html($payment_group_id, $payment_methods,$label)
    {
        $result = '';

    // Fieldset wrapper
    $result .= '<fieldset class="spell-payment-group" id="spell-payment-group-' . esc_attr( $payment_group_id ) . '">';
    $result .= '<legend class="spell-payment-group__legend">' . $label . '</legend>';

    $shared_settings = new WC_Spell_Gateway_Payment_Settings();
    $order= json_decode($shared_settings->get_option('multilink_method_order'), true);

    $payment_methods = $this->sort_methods_by_order($payment_methods, $order);
    
    if ( count( $payment_methods ) > 0 ) {
        $result .= '<div class="spell-payment-group__grid">';
        foreach ( $payment_methods as $index => $payment_method ) {
            $method_id    = esc_attr( $payment_method['id'] );
            $method_label = esc_html( $payment_method['label'] );
            $method_logo  = esc_url( $payment_method['logo'] );

            $input_id = "spell-payment-method-{$payment_group_id}-{$index}";

            // Wrap entire clickable area inside a <label>
            $result .= '<label class="spell-payment-group__item" for="' . esc_attr( $input_id ) . '">';
            $result .= '<input type="radio" id="' . esc_attr( $input_id ) . '" class="spell-payment-method__input" name="spell-payment-method-' . esc_attr( $payment_group_id ) . '" value="' . $method_id . '" ' . ( $index === 0 ? 'checked="checked"' : '' ) . ' />';
            $result .= '<span class="spell-payment-method__custom-radio"></span>';

            if ( ! empty( $method_logo ) ) {
                $result .= '<span class="spell-payment-method__logo"><img alt="' . $method_label . ' logo" src="' . $method_logo . '" /></span>';
            }

            $result .= '<span class="spell-payment-method__text">' . $method_label . '</span>';
            $result .= '</label>';
        }
        $result .= '</div>';
    } else {
        $single = $payment_methods[0];
        $result .= '<input type="hidden" class="spell-payment-method" name="spell-payment-method-' . esc_attr( $payment_group_id ) . '" value="' . esc_attr( $single['id'] ) . '" />';
        $result .= '<p class="spell-payment-group__single">' . esc_html( $single['label'] ) . '</p>';
    }

    $result .= '</fieldset>';

        return $result;
    }

    /**
     * @return string
     */
    public function get_notes()
    {
        $cart = WC()->cart->get_cart();
        $nameString = '';

        foreach ($cart as $key => $cart_item) {
            $cart_product = $cart_item['data'];
            $name = method_exists($cart_product, 'get_name') === true ? $cart_product->get_name() : $cart_product->name;
            
            if(isset($cart_item['quantity'])) {
                $name.=' x '.$cart_item['quantity'];
            }

            if (array_keys($cart)[0] == $key) {
                $nameString = $name;
            } else {
                $nameString = $nameString . ';' . $name;
            }
        }

        return $nameString;
    }


    /**
     * Normalize $_REQUEST data by unsetting redundant/unused payment methods.
     *
     * @param WC_Order $o
     */
    public function normalize_request(WC_Order $o)
    {
        $payment_method = $o->get_payment_method();

        if ($payment_method !== 'klix-payments') {
            $this->sanitize_spell_payment_methods($payment_method);
        }
    }

    /**
     * Sanitize and cleanup the request data from redundant/unnecessary payment methods.
     *
     * @param $payment_method
     */
    private function sanitize_spell_payment_methods($payment_method)
    {
        $key_to_find = 'spell-payment-method-';
        $selected_payment_method = $key_to_find . $payment_method;

        if (!isset($_REQUEST[$selected_payment_method])) {
            return;
        }
        $filtered_request = $_REQUEST;

        foreach ($filtered_request as $key => $value) {
            if (preg_match("/^{$key_to_find}/", $key) && $key !== $selected_payment_method) {
                unset($filtered_request[$key]);
            }
        }

        if (isset($filtered_request[$selected_payment_method])) {
            $filtered_request['spell-payment-method'] = $filtered_request[$selected_payment_method];
            unset($filtered_request[$selected_payment_method]);
        }

        $_REQUEST = $filtered_request;
    }
}
