<?php

define('SPELL_MODULE_VERSION', 'v1.5.1');
define("ROOT_URL", "https://portal.klix.app");

class SpellAPI
{
    public function __construct($private_key, $brand_id, $logger, $debug)
    {
        $this->private_key = $private_key;
        $this->brand_id = $brand_id;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    public function create_payment($params)
    {
        $this->log_info("loading payment form");
        return $this->call('POST', '/purchases/', $params);
    }

    public function isRequestTheSame($new,$old)
    {
        return $new['language'] == $old['language'] and $new['currency'] == $old['currency'];
    }

    public function hidePayLater($payment_methods,$amount)
    {
        $shared_settings = new WC_Spell_Gateway_Payment_Settings();

        if($shared_settings->get_option('hide_pay_later')=='yes' 
        and $shared_settings->get_option('hide_pay_later_amount')!=='' 
        and floatval($shared_settings->get_option('hide_pay_later_amount'))*100>$amount) 
        {
            $pay_later_terminals=[
                "any"=>"klix_pay_later",
                "LV"=>"klix_pay_later_lv",
                "LT"=>"klix_pay_later_lt",
                "EE"=>"klix_pay_later_ee"
            ];
            foreach($pay_later_terminals as $country =>$terminal_name)
            {
                if (isset($payment_methods['by_country'][$country]) and ($key = array_search($terminal_name, $payment_methods['by_country'][$country])) !== false) {
                    unset($payment_methods['by_country'][$country][$key]);
                }
            }
        }
        return $payment_methods;
    }

    public function payment_methods($currency, $language,$amount)
    {
        //get payment methods from cache
        $payment_methods=get_transient('spell-payment-methods');

        $current_request=['currency'=>$currency,'language'=>$language,'amount'=>$amount];
        $previous_request=get_transient('spell-payment-method-request');

        if($payment_methods and $this->isRequestTheSame($current_request,$previous_request)) {
            $this->log_info("payment methods received from cache");
            $payment_methods=$this->hidePayLater($payment_methods,$amount);
            return $payment_methods;
        }

        $this->log_info("fetching payment methods");

        $payment_methods= $this->call(
            'GET',
            "/payment_methods/?brand_id={$this->brand_id}&currency={$currency}&language={$language}"
        );


        if($payment_methods!=null) {
            set_transient('spell-payment-methods',$payment_methods,300);
            set_transient('spell-payment-method-request',$current_request,300);
        }

        $payment_methods=$this->hidePayLater($payment_methods,$amount);
       
        return $payment_methods;
    }

    public function was_payment_successful($payment_id)
    {
        $this->log_info(sprintf("validating payment: %s", $payment_id));
        $result = $this->call('GET', "/purchases/{$payment_id}/");
        $this->log_info(sprintf(
            "success check result: %s",
            var_export($result, true)
        ));
        return $result && $result['status'] == 'paid';
    }

    public function get_payment($payment_id)
    {
        return $this->call('GET', "/purchases/{$payment_id}/");
    }

    public function refund_payment($payment_id, $params)
    {
        $this->log_info(sprintf("refunding payment: %s", $payment_id));

        $result = $this->call('POST', "/purchases/{$payment_id}/refund/", $params);

        $this->log_info(sprintf(
            "payment refund result: %s",
            var_export($result, true)
        ));

        return $result;
    }

    private function call($method, $route, $params = [])
    {
        $private_key = $this->private_key;
        if (!empty($params)) {
            $params = json_encode($params);
        }

        $response = $this->request(
            $method,
            sprintf("%s/api/v1%s", ROOT_URL, $route),
            $params,
            [
                'Content-type: application/json',
                'Authorization: ' . "Bearer " . $private_key,
            ]
        );

        $this->log_info(sprintf('received response: %s', $response));
        $result = json_decode($response, true);
        if (!$result) {
            $this->log_error('JSON parsing error/NULL API response');
            return null;
        }

        if (!empty($result['errors'])) {
            $this->log_error('API error', $result['errors']);
            return null;
        }

        return $result;
    }

    private function request($method, $url, $params = [], $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_PUT, 1);
        }
        if ($method == 'PUT' or $method == 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        // curl_setopt($conn, CURLOPT_FAILONERROR, false);
        // curl_setopt($conn, CURLOPT_HTTP200ALIASES, (array) 400);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->log_info(sprintf(
            "%s `%s`\n%s\n%s",
            $method,
            $url,
            var_export($params, true),
            var_export($headers, true)
        ));
        $response = curl_exec($ch);
        switch ($code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
            case 200:
            case 201:
                break;
            default:
                $this->log_error(
                    sprintf("%s %s: %d", $method, $url, $code),
                    $response
                );
        }
        if (!$response) {
            $this->log_error('curl', curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }

    public function log_info($text, $error_data = null)
    {
        if ($this->debug) {
            $this->logger->log("INFO: " . $text . ";");
        }
    }

    public function log_error($error_text, $error_data = null)
    {
        $error_text = "ERROR: " . $error_text . ";";
        if ($error_data) {
            $error_text .= " ERROR DATA: " . var_export($error_data, true) . ";";
        }
        $this->logger->log($error_text);
    }
}
