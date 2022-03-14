<?php

class WC_Spell_Logger
{
    const LOGGER_HANDLER = 'spell';

    /**
     * @var WC_Logger
     */
    private $logger;

    public function __construct()
    {
        $this->logger = new WC_Logger();
    }

    /**
     * @param $message
     */
    public function log($message)
    {
        $this->logger->add(self::LOGGER_HANDLER, $message);
    }
}
