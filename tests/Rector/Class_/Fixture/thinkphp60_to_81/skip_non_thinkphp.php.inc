<?php

namespace app\service;

class PaymentService
{
    private $gateway;
    private $config;
    
    public function __construct($gateway, $config)
    {
        $this->gateway = $gateway;
        $this->config = $config;
    }
    
    public function process($amount)
    {
        return true;
    }
}

?>
