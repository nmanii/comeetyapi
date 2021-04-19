<?php
namespace AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\User;

/**
 * @ORM\Entity()
 */
class UserStatistics
{
   /**
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\Id
     */
    private $user;

    /**
     * @ORM\Column(type="smallint")
     */
    private $participationCount;

    /**
     * @ORM\Column(type="smallint")
     */
    private $eventOrganisationCount;

    /**
     * @ORM\Column(type="smallint")
     */
    private $subscriberCount;

    /**
     * @ORM\Column(type="smallint")
     */
    private $commitmentScore;

    public function __construct()
    {
        $this->participationCount = 0;
        $this->eventOrganisationCount = 0;
        $this->commitmentScore = 3;
        $this->subscriberCount = 0;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParticipationCount()
    {
        return $this->participationCount;
    }

    /**
     * @param mixed $participationCount
     */
    public function setParticipationCount($participationCount)
    {
        $this->participationCount = $participationCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEventOrganisationCount()
    {
        return $this->eventOrganisationCount;
    }

    /**
     * @param mixed $eventOrganisationCount
     */
    public function setEventOrganisationCount($eventOrganisationCount)
    {
        $this->eventOrganisationCount = $eventOrganisationCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCommitmentScore()
    {
        return $this->commitmentScore;
    }

    /**
     * @param mixed $commitmentScore
     */
    public function setCommitmentScore($commitmentScore)
    {
        $this->commitmentScore = $commitmentScore;
    }

    /**
     * @return mixed
     */
    public function getSubscriberCount()
    {
        return $this->subscriberCount;
    }

    /**
     * @param mixed $subscriberCount
     */
    public function setSubscriberCount($subscriberCount)
    {
        $this->subscriberCount = $subscriberCount;
    }

    public function getParticipationLevel()
    {
        if($this->getParticipationCount() > 9) {
            $level = 4;
        } elseif($this->getParticipationCount() > 4) {
            $level = 3;
        }
        elseif($this->getParticipationCount() > 0) {
            $level = 2;
        } else {
            $level = 1;
        }
        return $level;
    }
}
