<?php
namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findActiveWithProfileFilledUsers()
    {   $queryBuilder = $this->createQueryBuilder('u');
        $query = $queryBuilder
            ->select('u')
            ->join('u.profile', 'p')
            ->andWhere('u.confirmed = :confirmed')
            ->andWhere('u.active = :active')
            ->setParameter('confirmed', true)
            ->setParameter('active', true)
            ->getQuery();

        return $query
            ->getResult();
    }

    public function findForFounderWelcome()
    {
        $startDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $startDateTime->sub(new \DateInterval('P7D'));
        $endDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $endDateTime->sub(new \DateInterval('P3D'));

        $queryBuilder = $this->createQueryBuilder('u');
        $query = $queryBuilder
            ->select('u')
            ->join('u.profile', 'p')
            ->leftJoin(
                'AppBundle\Entity\MailLog',
                'ml',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'ml.user = u.id AND ml.mailName = \'founder_welcome\''
            )
            ->andWhere('u.confirmed = :confirmed')
            ->andWhere('u.active = :active')
            ->andWhere('u.creationDateTime > :startDateTimeUTC')
            ->andWhere('u.creationDateTime < :endDateTimeUTC')
            ->andWhere('ml.id IS NULL')
            ->setParameter('confirmed', true)
            ->setParameter('active', true)
            ->setParameter('startDateTimeUTC', $startDateTime->format('Y-m-d H:i:s'))
            ->setParameter('endDateTimeUTC', $endDateTime->format('Y-m-d H:i:s'))
            ->getQuery();

        return $query
            ->getResult();
    }

    public function findForFounderNonParticipationEnquiry()
    {

        $endDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $endDateTime->sub(new \DateInterval('P14D'));

        $queryBuilder = $this->createQueryBuilder('u');
        $query = $queryBuilder
            ->select('u')
            ->join('u.profile', 'p')
            ->leftJoin(
                'AppBundle\Entity\MailLog',
                'ml',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'ml.user = u.id AND ml.mailName = \'founder_non_participation_enquiry\''
            )
            ->leftJoin(
                'AppBundle\Entity\EventUser',
                'eu',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'eu.user = u.id'
            )
            ->andWhere('u.confirmed = :confirmed')
            ->andWhere('u.active = :active')
            ->andWhere('u.creationDateTime < :endDateTimeUTC')
            ->andWhere('ml.id IS NULL')
            ->andWhere('eu.id is null')
            ->setParameter('confirmed', true)
            ->setParameter('active', true)
            ->setParameter('endDateTimeUTC', $endDateTime->format('Y-m-d H:i:s'))
            ->getQuery();


        return $query
            ->getResult();
    }
}