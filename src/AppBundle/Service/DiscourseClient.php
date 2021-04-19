<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 25/08/2018
 * Time: 13:57
 */

namespace AppBundle\Service;


class DiscourseClient
{
    private $username;
    private $apiKey;
    private $httpClient;

    public function  __construct($username, $apiKey, $httpClient)
    {
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;
    }

    public function getUserByExternalId($externalId)
    {
        $response = $this->httpClient->get('/users/by-external/'.$externalId.'.json?api_key='.$this->apiKey.'&api_username='.$this->username);

        $data = \GuzzleHttp\json_decode($response->getBody(), true);
        return $data;
    }

    public function logoutUser($discourseUserId)
    {
        $response = $this->httpClient->post('/admin/users/'.$discourseUserId.'/log_out?api_key='.$this->apiKey.'&api_username='.$this->username);

        $data = \GuzzleHttp\json_decode($response->getBody(), true);
        return $data;
    }
}