<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Command;

/**
 * Get PayPal token by providing Client Id (-i or --id) and Client Secret (-s or --secret)
 */
class PaypalToken implements CommandInterface
{
    private string $id;
    private string $secret;

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setSecret($secret): void
    {
        $this->secret = $secret;
    }

    public function run()
    {
        $login = \App\DataSource\PayPal::login($this->id, $this->secret);
        $date = new \DateTime();
        echo '#Generated ' . $date->format('Y-m-d H:i:s');
        $date->add(new \DateInterval('PT' . $login['expires_in'] . 'S'));
        echo ', expires ' . $date->format('Y-m-d H:i:s') . PHP_EOL;
        echo 'PAYPAL_TOKEN=' . $login['access_token'] . PHP_EOL;
    }

}