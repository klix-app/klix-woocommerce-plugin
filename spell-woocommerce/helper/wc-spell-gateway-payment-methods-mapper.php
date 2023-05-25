<?php

class WC_Spell_Gateway_Payment_Methods_Mapper
{
    const IMAGE_BASE_URL = 'https://portal.klix.app';
    private $payment_method_order=["bank_transfer","klix_card","klix_pay_later"];
    private $api_response;
    private $active_country;
    private $result = [];

    public function __construct($api_response)
    {
        $this->api_response = $api_response;
        $this->active_country = $this->get_active_country();
    }

    /**
     * @return array
     */
    public function get_payment_groups()
    {
        $this->map_payment_groups();

        return $this->result;
    }

    private function map_payment_groups()
    {
        if(!array_key_exists('payment_method_groups',$this->api_response)) {
            return;
        }
            
        $payment_groups = $this->api_response['payment_method_groups'];

        foreach ($payment_groups as $payment_group) {
            if ($this->is_payment_group_available($payment_group)) {
                $this->result[$payment_group['name']] = $this->map_payment_group($payment_group);
            }
        }
        $new_method_order=[];
        foreach($this->payment_method_order as $payment_method){
            if(array_key_exists($payment_method,$this->result)){
                $new_method_order[$payment_method]=$this->result[$payment_method];
            }
        }

        $this->result=$new_method_order;
    }

    private function map_payment_group($payment_group)
    {
        return [
            'id' => isset($payment_group['name']) ? $payment_group['name'] : '',
            'label' => isset($payment_group['label']) ? $payment_group['label'] : '',
            'logo' => isset($payment_group['logo']) ? self::IMAGE_BASE_URL . $payment_group['logo'] : '',
            'methods' => $this->map_group_methods($payment_group['methods']),
        ];
    }

    /**
     * Check whether payment group is available for the selected country
     *
     * @param $payment_group
     * @return bool
     */
    private function is_payment_group_available($payment_group)
    {
        if (empty($payment_group['methods'])) {
            return false;
        }

        foreach ($payment_group['methods'] as $payment_method) {
            if ($this->is_payment_method_available_for_country($payment_method)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $payment_method
     * @return bool
     */
    private function is_payment_method_available_for_country($payment_method)
    {
        $countries = $this->api_response["by_country"];
        $active_country = $this->active_country;

        /**
         * Check if the payment method is available for certain country
         */
        if (isset($countries[$active_country])) {
            $country_payments = $countries[$active_country];

            foreach ($country_payments as $country_payment) {
                if ($payment_method === $country_payment) {
                    return true;
                }
            }
        }

        /**
         * Check if the payment method is available for "all" countries
         */
        if (isset($countries['any'])) {
            $all_country_payments = $countries['any'];

            foreach ($all_country_payments as $country_payment) {
                if ($payment_method === $country_payment) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $payment_methods
     * @return array
     */
    private function map_group_methods($payment_methods)
    {
        $result = [];

        foreach ($payment_methods as $payment_method) {
            if ($this->is_payment_method_available_for_country($payment_method)) {
                $result[] = $this->map_payment_method($payment_method);
            }
        }

        return $result;
    }

    /**
     * @param $payment_method
     * @return array
     */
    private function map_payment_method($payment_method)
    {
        return [
            'id' => $payment_method,
            'label' => $this->map_payment_method_name($payment_method),
            'logo' => $this->map_payment_method_logo($payment_method),
        ];
    }

    /**
     * Map payment method logo
     *
     * @param $payment_method
     * @return mixed|string
     */
    private function map_payment_method_logo($payment_method)
    {
        $logos = $this->api_response['logos'];

        return array_key_exists($payment_method, $logos) ? self::IMAGE_BASE_URL . $logos[$payment_method] : '';
    }

    /**
     * Map payment method name
     *
     * @param $payment_method
     * @return mixed|string
     */
    private function map_payment_method_name($payment_method)
    {
        $names = $this->api_response['names'];

        return array_key_exists($payment_method, $names) ? $names[$payment_method] : '';
    }

    /**
     * @return array
     */
    private function get_country_options()
    {
        if(!array_key_exists('by_country',$this->api_response)) {
            return null;
        }
        return array_values(array_unique(
            array_keys($this->api_response['by_country'])
        ));
    }

    /**
     * Get active country from the billing address
     *
     * @param $country_options
     * @return mixed|string
     */
    private function get_active_country()
    {
        $country_options = $this->get_country_options();
        if($country_options==null) {
            return '';
        }
        /**
         * Search for the "any" country value and move it to the end of the list.
         */
        $any_index = array_search('any', $country_options);

        if ($any_index !== false) {
            array_splice($country_options, $any_index, 1);
            $country_options = array_merge($country_options, ['any']);
        }

        /**
         * Preselect country
         */
        $geo = new WC_Geolocation();
        $user_ip = $geo->get_ip_address();
        $user_geo = $geo->geolocate_ip($user_ip);
        $detected_country = $user_geo['country'];
        $selected_country = '';
        $billing_country = $this->get_billing_country();

        if ($billing_country !== '') {
            $selected_country = $billing_country;
        } elseif (in_array($detected_country, $country_options)) {
            $selected_country = $detected_country;
        } elseif ($any_index !== false) {
            $selected_country = 'any';
        } elseif (count($country_options) > 0) {
            $selected_country = $country_options[0];
        }

        return $selected_country;
    }

    /**
     * @return mixed|string
     */
    private function get_billing_country()
    {
        $billing_country='';

        if (isset(WC()->checkout) && WC()->checkout instanceof WC_Checkout) {
          
            $billing_country = WC()->checkout->get_value('billing_country');
            $billing_country = empty($billing_country) ? WC()->countries->get_base_country() : $billing_country;
            $allowed_countries = WC()->countries->get_allowed_countries();

            if (!array_key_exists($billing_country, $allowed_countries)) {
                $billing_country = current(array_keys($allowed_countries));
            }
        }

        return $billing_country;
    }
}
