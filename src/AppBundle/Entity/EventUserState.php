<?php
namespace AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="event_user_states")
 * @ORM\Entity()
 */
class EventUserState
{
    const CONFIRMED = 'confirmed';
    const CANCELLED = 'cancelled';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="EventUser", inversedBy="states")
     */
    private $eventUser;

    /**
     * @ORM\Column(type="string", length=191)
     * waiting, confirmed, invited
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDateTime;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEventUser()
    {
        return $this->eventUser;
    }

    /**
     * @param mixed $eventUser
     */
    public function setEventUser($eventUser)
    {
        $this->eventUser = $eventUser;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreationDateTime()
    {
        return $this->creationDateTime;
    }

    /**
     * @param mixed $creationDateTime
     */
    public function setCreationDateTime($creationDateTime)
    {
        $this->creationDateTime = $creationDateTime;

        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }
}
