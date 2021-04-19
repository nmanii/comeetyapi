<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 14/07/2017
 * Time: 22:00
 */

namespace AppBundle\Service;



class GoogleTimeZoneService
{
    private $httpClient;
    private $apiKey;

    public function __construct($httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = 'AIzaSyDXPgI24cnosJo7p-S9Q-KRtbaaT7ycTIU';
    }

    public function getCurrentTimeZoneDetails($latitude, $longitude)
    {
        $response = $this->httpClient->get('maps/api/timezone/json?location='.$latitude.','.$longitude.'&timestamp='.time().'&key='.$this->apiKey);

        $data = \GuzzleHttp\json_decode($response->getBody(), true);
        return $data;
    }
}