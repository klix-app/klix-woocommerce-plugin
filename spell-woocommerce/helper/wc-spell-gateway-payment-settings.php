<?php

/**
 * A separate class to share the module settings across different payment methods.
 */
class WC_Spell_Gateway_Payment_Settings extends WC_Settings_API
{
    public $id = 'klix-payments';

    /**
     * @return array
     */
    public function get_settings()
    {
        if (!is_array($this->settings) || empty($this->settings)) {
            $this->init_settings();
        }

        return $this->settings;
    }
}
