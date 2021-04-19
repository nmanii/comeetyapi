<?php
namespace AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="event_users", uniqueConstraints={@ORM\UniqueConstraint(name="unique_event_user",columns={"user_id", "event_id"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EventUserRepository")
 *
 */
class EventUser
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Event")
     * @ORM\JoinColumn(nullable=false)
     */
    private $event;

    /**
     * @ORM\OneToOne(targetEntity="EventUserState")
     * waiting, confirmed, invited
     */
    private $currentState;

    /**
     * @ORM\OneToMany(targetEntity="EventUserState", mappedBy="eventUser")
     * waiting, confirmed, invited
     */
    private $states;

    /**
     * @ORM\Column(type="boolean")
     * If we asked for the user feedback for the event
     */
    private $feedbackRequested;

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
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return EventUserState currentState
     * used by virtual method in serializer
     */
    public function getState()
    {
        return $this->currentState;
    }

    /**
     * @return EventUserState currentState
     */
    public function getCurrentState()
    {
        return $this->currentState;
    }

    /**
     * @param EventUserState $currentState
     * @return $this
     */
    public function setCurrentState(EventUserState $currentState)
    {
        $currentState->setEventUser($this);
        $this->currentState = $currentState;
        $this->addState($currentState);

        return $this;
    }

    protected function addState(EventUserState $state)
    {
        return $this->states[] = $state;
    }

    public function getStates()
    {
        return $this->states;
    }

    public function setFeedbackRequested($feedbackRequested)
    {
        $this->feedbackRequested = $feedbackRequested;
    }

    
    public function __construct()
    {
       $this->feedbackRequested = 0;
    }


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
    
}
