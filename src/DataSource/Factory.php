<?php

namespace App\DataSource;

class Factory
{
    public function createMercury(): Mercury
    {
        return new Mercury($this->sourceToken(Mercury::CONFIG_NAME));
    }

    public function createPayPal(): PayPal
    {
        return new PayPal($this->sourceToken(PayPal::CONFIG_NAME));
    }

    public function createStripe(): Stripe
    {
        return new Stripe($this->sourceToken(Stripe::CONFIG_NAME));
    }

    /**
     * @param string $source
     * @return string
     */
    private function sourceToken(string $source): string
    {
        $key = strtoupper($source) . '_TOKEN';
        if (!isset($_ENV[$key])) {
            throw new \UnderflowException("$key is not defined in .env");
        }
        return $_ENV[$key];
    }
}