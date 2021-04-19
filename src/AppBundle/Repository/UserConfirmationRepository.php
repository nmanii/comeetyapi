<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 30/08/2017
 * Time: 14:52
 */

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class UserConfirmationRepository extends EntityRepository
{
    public function findNonConfirmedUsers()
    {
            $queryBuilder = $this->createQueryBuilder('uc');
            $query = $queryBuilder
                ->select('uc')
                ->leftJoin('uc.user', 'u')
                ->andWhere('u.confirmed = :confirmed')
                ->setParameter('confirmed', false)
                ->getQuery();

            return $query
                ->getResult();
    }
}