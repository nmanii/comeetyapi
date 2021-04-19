<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 14/07/2017
 * Time: 22:00
 */

namespace AppBundle\Service;



class SendGridTemplateEmailService
{
    private $httpClient;
    private $apiKey;
    private $templateList;

    public function __construct($httpClient, $apiKey, $templateList)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->templateList = $templateList;
    }

    public function send($data)
    {
        $headers = ['Authorization' => 'Bearer '.$this->apiKey, 'Content-type' => 'application/json'];

        $this->httpClient->post(
            '/v3/mail/send',
            [
                'headers' => $headers,
                'json' => $data
            ]
        );
    }

    public function getTemplateIdByType($type) {
        if(!isset($this->templateList[$type])) {
            throw new \Exception('sendgrid_template_type_not_exist');
        }
        return $this->templateList[$type];
    }
}