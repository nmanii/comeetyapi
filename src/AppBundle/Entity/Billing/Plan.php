<?php

namespace AppBundle\Entity\Billing;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Plan
 * @package AppBundle\Entity\Billing
 * @ORM\Entity()
 */
class Plan
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount;

    /**
     * @ORM\Column(type="integer")
     */
    private $interval;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $currency;

    /**
     * @ORM\Column(type="boolean")
     * If we asked for the user feedback for the event
     */
    private $available;

    /**
     * @ORM\Column(type="integer")
     */
    private $eventRegistrationMaximumLimit;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDateTime;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $roleName;

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
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @param mixed $interval
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return mixed
     */
    public function isAvailable()
    {
        return $this->available;
    }

    /**
     * @param mixed $available
     */
    public function setAvailable($available)
    {
        $this->available = $available;
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
    }

    /**
     * @return mixed
     */
    public function getEventRegistrationMaximumLimit()
    {
        return $this->eventRegistrationMaximumLimit;
    }

    /**
     * @param mixed $eventRegistrationMaximumLimit
     */
    public function setEventRegistrationMaximumLimit($eventRegistrationMaximumLimit)
    {
        $this->eventRegistrationMaximumLimit = $eventRegistrationMaximumLimit;
    }

    /**
     * @return mixed
     */
    public function getRoleName()
    {
        return $this->roleName;
    }

    /**
     * @param mixed $roleName
     */
    public function setRoleName($roleName)
    {
        $this->roleName = $roleName;
    }


}
