<?php
namespace AppBundle\Repository;

use AppBundle\Entity\UserLink;
use Doctrine\ORM\EntityRepository;

class UserLinkRepository extends EntityRepository
{
    public function findByUserId($userId)
    {
        $results = $this->getEntityManager()->createQueryBuilder()
             ->select('ul')
             ->from('AppBundle:UserLink', 'ul')
             ->join('ul.user', 'u')
             ->join('ul.targetUser', 'tu')
             ->join('u.profile', 'p')
             ->where('ul.type != :deleteType')
             ->andwhere('tu.active = :active')
             ->andwhere('ul.type != :blockType')
             ->andWhere('IDENTITY(ul.user) = :userId'  )
             ->setParameter(':deleteType', UserLink::TYPE_DELETE)
             ->setParameter(':blockType', UserLink::TYPE_BLOCK)
             ->setParameter(':active', 1)
             ->setParameter(':userId', $userId)
             ->orderBy('p.firstName', 'ASC')
             ->getQuery()
             ->getResult();

        $data = [];
        foreach($results as $result) {
            $data[$result->getTargetUser()->getId()] = $result;
        }
        return $data;
    }

    public function findFollowersByTargetId($targetUserId)
    {
        $results = $this->getEntityManager()->createQueryBuilder()
            ->select('ul')
            ->addSelect('ul3')
            ->from('AppBundle:UserLink', 'ul')
            ->join('ul.user', 'u')
            ->join('u.profile', 'p')
            ->leftJoin('AppBundle:UserLink', 'ul2', 'WITH', 'ul.targetUser = ul2.user AND ul2.targetUser = ul.user AND ul2.type = :blockType')
            ->leftJoin('AppBundle:UserLink', 'ul3', 'WITH', 'ul.targetUser = ul3.user AND ul3.targetUser = ul.user AND ul3.type != :blockType')
            ->where('ul.type = :followType')
            ->andWhere('IDENTITY(ul.targetUser) = :targetUserId')
            ->andWhere('ul2.id is NULL')
            ->andwhere('u.active = :active')
            ->setParameter(':active', 1)
            ->setParameter(':followType', UserLink::TYPE_FOLLOW)
            ->setParameter(':targetUserId', $targetUserId)
            ->setParameter(':blockType', UserLink::TYPE_BLOCK)
            ->orderBy('p.firstName', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach($results as $key=>$result) {
            if($result !=null) {
                if ($key % 2 === 0) {
                    if(!isset($data[$result->getUser()->getId()])) {
                        $data[$result->getUser()->getId()] = [];
                    }
                    $data[$result->getUser()->getId()]['followerLink'] = $result;
                } else {
                    if(!isset($data[$result->getTargetUser()->getId()])) {
                        $data[$result->getTargetUser()->getId()] = [];
                    }
                    $data[$result->getTargetUser()->getId()]['link'] = $result;
                }
            }
        }
        return $data;
    }

    /**
     * Return all userLink that are not block or paused by the target User
     * Used for notifications
     * @param $targetUserId
     * @return array
     */
    public function findActiveFollowersByTargetId($targetUserId)
    {
        $results = $this->getEntityManager()->createQueryBuilder()
            ->select('ul')
            ->from('AppBundle:UserLink', 'ul')
            ->join('ul.user', 'u')
            ->join('u.profile', 'p')
            ->leftJoin('AppBundle:UserLink', 'ul2', 'WITH', 'ul.targetUser = ul2.user AND ul2.targetUser = ul.user AND (ul2.type = :blockType OR ul2.type = :pauseType)')
            ->where('ul.type = :followType')
            ->andWhere('IDENTITY(ul.targetUser) = :targetUserId')
            ->andWhere('ul2.id is NULL')
            ->andwhere('u.active = :active')
            ->setParameter(':active', 1)
            ->setParameter(':followType', UserLink::TYPE_FOLLOW)
            ->setParameter(':targetUserId', $targetUserId)
            ->setParameter(':blockType', UserLink::TYPE_BLOCK)
            ->setParameter(':pauseType', UserLink::TYPE_PAUSE)
            ->orderBy('p.firstName', 'ASC')
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function findPausedByUserId($userId)
    {
        $results = $this->getEntityManager()->createQueryBuilder()
            ->select('ul')
            ->from('AppBundle:UserLink', 'ul')
            ->join('ul.targetUser', 'tu')
            ->where('ul.type = :type')
            ->andWhere('IDENTITY(ul.user) = :userId'  )
            ->andwhere('tu.active = :active')
            ->setParameter(':active', 1)
            ->setParameter(':type', UserLink::TYPE_PAUSE)
            ->setParameter(':userId', $userId)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach($results as $result) {
            $data[$result->getTargetUser()->getId()] = $result;
        }
        return $data;
    }

    public function findBlockedByUserId($userId)
    {
        $results = $this->getEntityManager()->createQueryBuilder()
            ->select('ul')
            ->from('AppBundle:UserLink', 'ul')
            ->join('ul.targetUser', 'tu')
            ->where('ul.type = :type')
            ->andWhere('IDENTITY(ul.user) = :userId'  )
            ->andwhere('tu.active = :active')
            ->setParameter(':active', 1)
            ->setParameter(':type', UserLink::TYPE_BLOCK)
            ->setParameter(':userId', $userId)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach($results as $result) {
            $data[$result->getTargetUser()->getId()] = $result;
        }
        return $data;
    }

    public function getSubscriberCountIndexedByUser()
    {
        $rows = $this->getEntityManager()
            ->createQuery(
                'SELECT count(ul.targetUser) as nb, tu.id
                  FROM AppBundle:UserLink ul 
                  INNER JOIN ul.targetUser tu
                  INDEX BY tu.id
                  WHERE ul.type = :type
                  AND tu.active = :active
                  GROUP BY ul.targetUser
                  '
            )
            ->setParameter('type', UserLink::TYPE_FOLLOW)
            ->setParameter(':active', 1)
            ->getArrayResult();
        return $rows;
    }
}