<?php
// src/AppBundle/Services/GoogleClient.php
namespace AppBundle\Services;

use Google_Client;

class GoogleClient
{
    /**
     * @var Google_Client client
     */
    protected $client;

    /**
     * GoogleClient constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $client = new Google_Client();
        $client->setApplicationName($config['applName']);
        $client->setDeveloperKey($config['apiKey']);

        $this->client = $client;
    }

    /**
     * @return Google_Client
     */
    public function getClient()
    {
        return $this->client;
    }


}