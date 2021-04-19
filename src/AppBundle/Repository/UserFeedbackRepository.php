<?php
namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class UserFeedbackRepository extends EntityRepository
{
    public function findUsersLastNoShow()
    {
        $rsm = new Query\ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('AppBundle:UserFeedback', 'uf');

        $rsm->addIndexByScalar('user_id');

        $query = $this->getEntityManager()->createNativeQuery(
            'select * 
            FROM (SELECT *
              FROM user_feedback uf
              WHERE category = :category
              AND rating = :noshow
              ORDER BY creation_date_time DESC) as subquery
            GROUP BY user_id',
            $rsm
        )
        ->setParameter('category', 'general')
        ->setParameter('noshow', 'noshow');
        return $query
            ->getResult();
    }
}