<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 14/07/2017
 * Time: 22:00
 */

namespace AppBundle\Service;



class GooglePlaceService
{
    private $httpClient;
    private $apiKey;

    public function __construct($httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = 'AIzaSyAYqHAB_lymicvtT2NfDmMjbBv5zfxdcQU';
    }

    public function getPlaceDetails($placeId)
    {
        $response = $this->httpClient->get('/maps/api/place/details/json?placeid='.$placeId.'&key='.$this->apiKey);

        $data = \GuzzleHttp\json_decode($response->getBody(), true);
        return $data;
    }
}