<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 19/10/2017
 * Time: 17:54
 */

namespace AppBundle\Service;


use AppBundle\Entity\MailLog;

class MailLogger
{
    private $entityManager;

    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function log($user, $mailName)
    {
        $mailLog = new MailLog();
        $mailLog->setUser($user)
            ->setMailName($mailName)
            ->setCreationDateTime(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->entityManager->persist($mailLog);
        $this->entityManager->flush();
    }
}