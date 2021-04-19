<?php
namespace AppBundle\Repository;

use AppBundle\Entity\EventUserState;
use Doctrine\ORM\EntityRepository;

class EventRepository extends EntityRepository
{
    public function findOpenEvents()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        //Show events which startDateTimeUTC was at max two hour before
        $dateTime->sub(new \DateInterval('PT2H'));
        $queryBuilder = $this->createQueryBuilder('e');
        $query = $queryBuilder
            ->select('e')
            ->andWhere('e.startDateTimeUTC > :startDateTimeUTC')
            ->andWhere('e.public = :public')
            ->orderBy('e.startDateTimeUTC', 'ASC')
            ->setParameter('startDateTimeUTC', $dateTime->format('Y-m-d H:i'))
            ->setParameter('public', true)
            ->getQuery();

        return $query
            ->getResult();
    }

    public function findPastEvents()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $query = $this->getEntityManager()->createQuery(
            'SELECT e
            FROM AppBundle:Event e
            LEFT JOIN AppBundle:EventUser eu WITH eu.event = e.id
            WHERE e.startDateTimeUTC < :startDateTimeUTC
            AND e.public = :public
            AND e.id IS NOT NULL
            ORDER BY e.startDateTimeUTC DESC'
        )
            ->setParameter('startDateTimeUTC', $dateTime->format('Y-m-d H:i:s'))
            ->setParameter('public', true);
        return $query
            ->getResult();
    }

    public function findPastRegisteredEventByUserId($userId)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $query = $this->getEntityManager()->createQuery(
            'SELECT e
            FROM AppBundle:Event e
            LEFT JOIN AppBundle:EventUser eu WITH eu.event = e.id
            JOIN eu.user u
            JOIN eu.currentState cs
            WHERE e.startDateTimeUTC < :startDateTimeUTC
            AND u.id = :userId
            AND e.id IS NOT NULL
            AND cs.name = :confirmedState
            ORDER BY e.startDateTimeUTC DESC'
        )
            ->setParameter('startDateTimeUTC', $dateTime->format('Y-m-d H:i:s'))
            ->setParameter('userId', $userId)
            ->setParameter('confirmedState', EventUserState::CONFIRMED);
        return $query
            ->getResult();
    }

    public function findUpcomingRegisteredEventByUserId($userId)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateTime->sub(new \DateInterval('PT2H'));
        $query = $this->getEntityManager()->createQuery(
            'SELECT e
            FROM AppBundle:Event e
            LEFT JOIN AppBundle:EventUser eu WITH eu.event = e.id
            JOIN eu.user u
            JOIN eu.currentState cs
            WHERE e.startDateTimeUTC > :startDateTimeUTC
            AND u.id = :userId
            AND e.id IS NOT NULL
            AND cs.name = :confirmedState
            ORDER BY e.startDateTimeUTC ASC'
        )
            ->setParameter('startDateTimeUTC', $dateTime->format('Y-m-d H:i:s'))
            ->setParameter('userId', $userId)
            ->setParameter('confirmedState', EventUserState::CONFIRMED);
        return $query
            ->getResult();
    }

    public function findUpcomingPendingInvitationEventByUserId($userId)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateTime->sub(new \DateInterval('PT2H'));
        $query = $this->getEntityManager()->createQuery(
            'SELECT e
            FROM AppBundle:Event e
            INNER JOIN AppBundle:EventUserInvitation eui WITH eui.event = e.id AND eui.invitedUser = :userId
            LEFT JOIN AppBundle:EventUser eu WITH eu.event = e.id AND eu.user = :userId
            LEFT JOIN eu.currentState cs
            WHERE e.startDateTimeUTC > :startDateTimeUTC
            AND e.id IS NOT NULL
            AND (cs.id IS NULL OR cs.name = :cancelledState)
            ORDER BY e.startDateTimeUTC DESC'
        )
            ->setParameter('startDateTimeUTC', $dateTime->format('Y-m-d H:i:s'))
            ->setParameter('userId', $userId)
            ->setParameter('cancelledState', EventUserState::CANCELLED);

        return $query
            ->getResult();
    }

    public function findPastRegisteredEventByUserPresence($userId, $encounteredUserId)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $query = $this->getEntityManager()->createQuery(
            'SELECT e
            FROM AppBundle:Event e
            INNER JOIN AppBundle:EventUser eu WITH eu.event = e.id AND eu.user = :userId
            INNER JOIN AppBundle:EventUser eu2 WITH eu2.event = e.id AND eu2.user = :encounteredUserId
            JOIN eu.currentState cs
            JOIN eu2.currentState cs2
            WHERE e.startDateTimeUTC < :startDateTimeUTC
            AND e.id IS NOT NULL
            AND cs.name = :confirmedState
            AND cs2.name = :confirmedState
            ORDER BY e.startDateTimeUTC DESC'
        )
            ->setParameter('startDateTimeUTC', $dateTime->format('Y-m-d H:i:s'))
            ->setParameter('userId', $userId)
            ->setParameter('encounteredUserId', $encounteredUserId)
            ->setParameter('confirmedState', EventUserState::CONFIRMED);
        return $query
            ->getResult();
    }


}