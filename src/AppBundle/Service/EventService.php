<?php

namespace AppBundle\Service;

use AppBundle\Entity\Event;
use AppBundle\Entity\User;
use AppBundle\Entity\UserProfile;

class EventService
{
    private $entityManager;

    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function calculateUTCInfos(Event $event)
    {
        if(empty($event->getStartDateTimeTimezone())) {
            $this->updateTimezoneFromAddress($event);
        }
        $eventDateTime = new \DateTime(
            $event->getStartDateTime()->format('Y-m-d H:i:s'),
            new \DateTimeZone(
                $event->getStartDateTimeTimeZone()
            )
        );
        $event->setStartDateTimeUTCOffset($eventDateTime->getOffset());
        $eventDateTime->setTimezone(new \DateTimeZone('UTC'));
        $event->setStartDateTimeUTC($eventDateTime);
        return $event;
    }

    private function updateTimezoneFromAddress(Event $event)
    {
        if($event->getLocation() !== null) {
            $event->setStartDateTimeTimeZone($event->getLocation()->getTimeZone());
        } else {
            $event->setStartDateTimeTimeZone('Europe/Paris');
        }

        return $event;
    }
}