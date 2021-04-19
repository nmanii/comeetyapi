<?php
namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class PasswordResetTokenRepository extends EntityRepository
{
    public function resetAllUserPasswordResetTokenByUserId($userId)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $query = $queryBuilder
            ->update('AppBundle:UserPasswordResetToken', 't')
            ->set('t.active', $queryBuilder->expr()->literal(false))
            ->where('IDENTITY(t.user) = :userId')
            ->setParameter(':userId', $userId)
            ->getQuery();
        $query->execute();
    }
}