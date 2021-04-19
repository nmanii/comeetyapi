<?php
namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class EventMessageRepository extends EntityRepository
{
   public function findMessageAuthorsByEventId($eventId)
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT u
            FROM AppBundle:User u
            INDEX BY u.id
            INNER JOIN AppBundle:EventMessage e WITH e.user = u.id
            WHERE IDENTITY(e.event) = :eventId
            GROUP BY u.id'
        )
            ->setParameter('eventId', $eventId);
        return $query
            ->getResult();
    }

    /**
     * @param $event
     * @param $privateOnly get public + private or only public + all message from current user
     */
    public function findByEventForCurrentUser($eventId, $publicOnly, $userId)
    {
        $sql = 'SELECT em
            FROM AppBundle:EventMessage em
            WHERE IDENTITY(em.event) = :eventId';

        if(true === $publicOnly) {
            $sql .= ' AND (em.private = 0';
            if($userId != null) {
                $sql .= '           OR (em.private = 1 AND IDENTITY(em.user) = :userId)';
            }
             $sql .= '        )';
        }
        $query = $this->getEntityManager()->createQuery(
            $sql
        )
            ->setParameter('eventId', $eventId);

        if(true === $publicOnly && $userId != null) {
            $query->setParameter('userId', $userId);
        }

        return $query
            ->getResult();
    }
}