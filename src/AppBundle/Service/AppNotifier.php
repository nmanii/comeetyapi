<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 18/06/2018
 * Time: 21:31
 */

namespace AppBundle\Service;


class AppNotifier
{
    private $key;
    private $httpClient;

    public function __construct($httpClient, $key)
    {
        $this->key = $key;
        $this->httpClient = $httpClient;
    }

    public function send($data)
    {
        $headers = ['Authorization' => 'key='.$this->key, 'Content-type' => 'application/json'];

        $this->httpClient->post(
            'fcm/send',
            [
                'headers' => $headers,
                'json' => $data
            ]
        );
    }
}