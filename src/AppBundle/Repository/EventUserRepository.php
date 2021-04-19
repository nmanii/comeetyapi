<?php
namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\EventUserState;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\DateTime;

class EventUserRepository extends EntityRepository
{
    public function countConfirmedUsersByEventId($eventId)
    {
        $queryBuilder = $this->createQueryBuilder('eu');
        $query = $queryBuilder
            ->select('count(IDENTITY(eu.user))')
            ->leftJoin('eu.currentState', 'eus')
            ->andWhere($queryBuilder->expr()->eq('IDENTITY(eu.event)', $eventId))
            ->andWhere('eus.name = :state')
            ->setParameter('state', EventUserState::CONFIRMED)
            ->getQuery();

        return $query
            ->getSingleScalarResult();
    }

    public function findConfirmedUsersByEventIdOrderByStateCreationDateTime($eventId)
    {
        $queryBuilder = $this->createQueryBuilder('eu');
        $query = $queryBuilder
            ->select('eu')
            ->leftJoin('eu.currentState', 'eus')
            ->andWhere($queryBuilder->expr()->eq('IDENTITY(eu.event)', $eventId))
            ->andWhere('eus.name = :state')
            ->setParameter('state', EventUserState::CONFIRMED)
            ->orderBy('eus.creationDateTime')
            ->getQuery();

        return $query
            ->getResult();
    }

    public function findOneConfirmedEventUserByEventIdAndUserId($eventId, $userId)
    {
        $queryBuilder = $this->createQueryBuilder('eu');
        $query = $queryBuilder
            ->select('eu')
            ->leftJoin('eu.currentState', 'eus')
            ->andWhere($queryBuilder->expr()->eq('IDENTITY(eu.event)', $eventId))
            ->andWhere($queryBuilder->expr()->eq('IDENTITY(eu.user)', $userId))
            ->andWhere('eus.name = :state')
            ->setParameter('state', EventUserState::CONFIRMED)
            ->getQuery();

        return $query
            ->getOneOrNullResult();
    }

    public function findConfirmedUsersWithFeedbackRequestPending()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateTime->sub(new \DateInterval('PT3H'));
        $queryBuilder = $this->createQueryBuilder('eu');
            $query = $queryBuilder
                ->select('eu')
                ->leftJoin('eu.currentState', 'eus')
                ->leftJoin('eu.event', 'e')
                ->andWhere('eus.name = :state')
                ->andWhere('eu.feedbackRequested = :feedbackRequested')
                ->andWhere('e.startDateTimeUTC < :startDateTimeUTC')
                ->setParameter('state', EventUserState::CONFIRMED)
                ->setParameter('feedbackRequested', false)
                ->setParameter('startDateTimeUTC', $dateTime->format('Y-m-d H:i:s'))
                ->getQuery();

            return $query
                ->getResult();
    }

    public function findPastConfirmedRegistrationCount()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $queryBuilder = $this->createQueryBuilder('eu');
        $query = $this->getEntityManager()->createQuery(
                    'SELECT u.id, COUNT(u.id) as registrationCount
            FROM AppBundle:EventUser eu
            LEFT JOIN eu.currentState eus
            LEFT JOIN eu.user u INDEX BY u.id
            LEFT JOIN eu.event e
            WHERE eus.name = :state
            AND e.startDateTimeUTC < :startDateTimeUTC
            GROUP BY u.id'
            )
            ->setParameter('state', EventUserState::CONFIRMED)
            ->setParameter('startDateTimeUTC', $dateTime->format('Y-m-d H:i:s'));
        return $query
            ->getResult(Query::HYDRATE_ARRAY);
    }

    public function findUsersWithMinimumConfirmedRegistrationButNoCreationAndNoRequestSent($minimumConfirmedRegistration)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $rsm = new Query\ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('AppBundle:User', 'u');

        //$rsm = new Query\ResultSetMapping();
        $rsm->addScalarResult('user_id', 'id');
        $rsm->addScalarResult('registrationCount', 'registrationCount');
        $rsm->addScalarResult('organisationCount', 'organisationCount');
        $rsm->addIndexByScalar('id');

        $query = $this->getEntityManager()->createNativeQuery(
            'SELECT u.*, COUNT(u.id) as registrationCount, organisationQuery.organisationCount
            FROM event_users eu
            LEFT JOIN event_user_states eus ON eu.current_state_id = eus.id
            LEFT JOIN app_users u on eu.user_id = u.id
            LEFT JOIN events e on eu.event_id = e.id
            LEFT JOIN (
                SELECT user_id, count(user_id) as organisationCount FROM (
                SELECT e.id as event_id, e.user_id as user_id, COUNT(eu.id) as participantsCount
                FROM events e
                LEFT JOIN event_users eu ON e.id = eu.event_id
                WHERE e.start_date_time_utc < :startDateTimeUTC
                GROUP BY e.id
                HAVING participantsCount > 1) as query
                GROUP BY user_id
            ) as organisationQuery ON u.id = organisationQuery.user_id
            LEFT JOIN mail_log ml ON ml.user_id = eu.user_id AND mail_name = "eventCreationRequestUserNeverCreated"
            WHERE eus.name = :state
            AND e.start_date_time_utc < :startDateTimeUTC
            AND organisationQuery.organisationCount is null
            AND ml.id IS NULL
            AND e.user_id !=u.id
            GROUP BY u.id
            HAVING registrationCount >= :minimumConfirmedRegistration',
            $rsm
        )
            ->setParameter('state', EventUserState::CONFIRMED)
            ->setParameter('startDateTimeUTC', $dateTime->format('Y-m-d H:i:s'))
            ->setParameter('minimumConfirmedRegistration', $minimumConfirmedRegistration);
        return $query
            ->getResult();
    }

    public function findUsersWithMinimumConfirmedRegistrationSinceLastEventCreationAndNoRequestSent($minimumConfirmedRegistration)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $rsm = new Query\ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('AppBundle:User', 'u');

        //$rsm = new Query\ResultSetMapping();
        $rsm->addScalarResult('user_id', 'id');
        $rsm->addScalarResult('registrationCount', 'registrationCount');
        $rsm->addScalarResult('organisationCount', 'organisationCount');
        $rsm->addScalarResult('lastEventOrganisationDate', 'lastEventOrganisationDate');
        $rsm->addIndexByScalar('id');

        $query = $this->getEntityManager()->createNativeQuery(
            'SELECT u.*, COUNT(u.id) as registrationCount, organisationQuery.lastEventOrganisationDate
            FROM event_users eu
            INNER JOIN event_user_states eus ON eu.current_state_id = eus.id
            INNER JOIN app_users u on eu.user_id = u.id
            INNER JOIN events e on eu.event_id = e.id
            INNER JOIN (
                SELECT MAX(start_date_time_utc) as lastEventOrganisationDate, user_id FROM (
                SELECT e.id as event_id, e.user_id as user_id, COUNT(eu.id) as participantsCount, start_date_time_utc
                FROM events e
                LEFT JOIN event_users eu ON e.id = eu.event_id
                WHERE e.start_date_time_utc < :startDateTimeUTC
                GROUP BY e.id
                HAVING participantsCount > 1) as query
                GROUP BY user_id
            ) as organisationQuery ON u.id = organisationQuery.user_id
            LEFT JOIN mail_log ml ON ml.user_id = eu.user_id AND mail_name = "eventCreationRequestUserAlreadyCreated"
            WHERE eus.name = :state
            AND e.start_date_time_utc < :startDateTimeUTC
            AND e.start_date_time_utc > organisationQuery.lastEventOrganisationDate
            AND ml.id IS NULL
            AND e.user_id !=u.id
            GROUP BY u.id
            HAVING registrationCount >= :minimumConfirmedRegistration',
            $rsm
        )
            ->setParameter('state', EventUserState::CONFIRMED)
            ->setParameter('startDateTimeUTC', $dateTime->format('Y-m-d H:i:s'))
            ->setParameter('minimumConfirmedRegistration', $minimumConfirmedRegistration);
        return $query
            ->getResult();
    }

    public function findPastEventOrganisationCount()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $rsm = new Query\ResultSetMapping();
        $rsm->addScalarResult('user_id', 'user_id');
        $rsm->addScalarResult('organisationCount', 'organisationCount');
        $rsm->addIndexByScalar('user_id');

        $query = $this->getEntityManager()->createNativeQuery(
            'SELECT user_id, count(user_id) as organisationCount FROM (
            SELECT e.id as event_id, e.user_id as user_id, COUNT(eu.id) as participantsCount
            FROM events e
            LEFT JOIN event_users eu ON e.id = eu.event_id
            WHERE e.start_date_time_utc < :startDateTimeUTC
            GROUP BY e.id
            HAVING participantsCount > 1) as query
            GROUP BY user_id',
            $rsm
        )->setParameter('startDateTimeUTC', $dateTime->format('Y-m-d H:i:s'));
                return $query
            ->getResult();
    }

    public function findPastCommitmentDataByUserId($userId)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $rsm = new Query\ResultSetMapping();
        $rsm->addScalarResult('event_id', 'event_id');
        $rsm->addScalarResult('role', 'role');
        $rsm->addScalarResult('event_datetime', 'event_datetime');
        $rsm->addScalarResult('name', 'state');
        $rsm->addScalarResult('rating', 'rating');
        $query = $this->getEntityManager()->createNativeQuery(
            '
            SELECT e.id as event_id, IF(eu.user_id = e.user_id, "creator", "participant") as role, 
            eus.name,  IF(eus.name = "cancelled", eus.creation_date_time, e.start_date_time_utc) as event_datetime, uf.rating
            FROM events e
            INNER JOIN event_users eu ON e.id = eu.event_id
            INNER JOIN event_user_states eus ON eu.current_state_id = eus.id
            LEFT JOIN 
                (
                  SELECT rating, event_id, reference_id
                  FROM user_feedback 
                  where rating = "noshow"
                  GROUP BY event_id, reference_id
                ) as uf ON uf.event_id = e.id and uf.reference_id = :userId
            WHERE (e.start_date_time_utc < :startDateTimeUTC
            OR eus.name = "cancelled")
            AND eu.user_id = :userId
            ORDER BY event_datetime ASC',
            $rsm
        )
        ->setParameter('startDateTimeUTC', $dateTime->format('Y-m-d H:i:s'))
        ->setParameter('userId', $userId);
        return $query
            ->getArrayResult();
    }

    public function haveBothUserConfirmedRegistrationToEvent($event, $user1, $user2)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $queryBuilder = $this->createQueryBuilder('eu');
        $query = $this->getEntityManager()->createQuery(
            'SELECT e.id
            FROM AppBundle:EventUser eu
            INNER JOIN eu.currentState eus
            INNER JOIN eu.event e
            INNER JOIN AppBundle:EventUser as eu2 WITH IDENTITY(eu2.user) = :user2 and IDENTITY(eu2.event) = IDENTITY(eu.event)
            INNER JOIN AppBundle:EventUserState eus2 WITH eus2.id = IDENTITY(eu2.currentState)
            WHERE eus.name = :state
            and eus2.name = :state
            AND IDENTITY(eu.event) = :event
            AND IDENTITY(eu.user) = :user1'
        )
            ->setParameter('state', EventUserState::CONFIRMED)
            ->setParameter('event', $event)
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2);

        try {
            $query->getSingleResult();
        } catch(NonUniqueResultException $ex) {
            return false;
        } catch(NoResultException $ex) {
            return false;
        }

        return true;
    }

    public function findPublicEventRegistrationUsageByUserAndPeriod($user, $startDate, $endDate=null)
    {
        $queryBuilder = $this->createQueryBuilder('eu');
        $queryBuilder = $queryBuilder
            ->select('count(IDENTITY(eu.user))')
            ->innerJoin('eu.states', 'eus')
            ->andWhere($queryBuilder->expr()->eq('IDENTITY(eu.user)', $user->getId()))
            ->andWhere('eus.name = :state')
            ->andWhere('eus.creationDateTime > :startDate')
            ->setParameter('state', EventUserState::CONFIRMED)
            ->setParameter('startDate', $startDate->format('Y-m-d 00:00:00'));
        if(!empty($endDate)) {
            $queryBuilder->andWhere('eus.creationDateTime <= :endDate')
                ->setParameter('endDate', $startDate->format('Y-m-d 23:59:59'));
        }
        $query = $queryBuilder->getQuery();

        return $query
            ->getSingleScalarResult();
    }

    public function findForEventNextDay()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateTime->add(new \DateInterval('P1D'));
        $queryBuilder = $this->createQueryBuilder('eu');
        $query = $queryBuilder
            ->select('eu')
            ->leftJoin('eu.currentState', 'eus')
            ->leftJoin('eu.event', 'e')
            ->andWhere('eus.name = :state')
            ->andWhere('e.startDateTime > :periodStartDateTime')
            ->andWhere('e.startDateTime < :periodEndDateTime')
            ->setParameter('state', EventUserState::CONFIRMED)
            ->setParameter('periodStartDateTime', $dateTime->format('Y-m-d').' 00:00:00')
            ->setParameter('periodEndDateTime', $dateTime->format('Y-m-d').' 23:59:59')
            ->getQuery();

        return $query
            ->getResult();
    }
}