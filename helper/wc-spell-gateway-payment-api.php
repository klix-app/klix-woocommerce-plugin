<?php

/**
 * A separate class to share the module settings across different payment methods.
 */
class WC_Spell_Gateway_Payment_Api
{
    /**
     * @var SpellAPI
     */
    private $cached_api;

    /**
     * @var WC_Spell_Gateway_Payment_Settings
     */
    private $shared_settings;

    /**
     * @var bool $debug
     */
    private $debug = true;

    public function __construct()
    {
        $this->shared_settings = new WC_Spell_Gateway_Payment_Settings();
    }

    public function spell_api()
    {
        if (!$this->cached_api) {
            $this->cached_api = new SpellAPI(
                $this->shared_settings->get_option('private-key'),
                $this->shared_settings->get_option('brand-id'),
                new WC_Spell_Logger(),
                $this->debug
            );
        }

        return $this->cached_api;
    }
}
