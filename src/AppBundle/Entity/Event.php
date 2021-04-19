<?php
namespace AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\User;
use \Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\EventCategory;

/**
 * @ORM\Table(name="events")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EventRepository")
 */
class Event
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $address;

    /**
     * @ORM\Column(type="string", nullable=true, length=191)
     */
    private $placeName;

    /**
     * @ORM\Column(type="datetime")
     */
    private $startDateTime;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $startDateTimeTimeZone;

    /**
     * @ORM\Column(type="datetime")
     */
    private $startDateTimeUTC;

    /**
     * @ORM\Column(type="smallint")
     */
    private $startDateTimeUTCOffset;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maximumCapacity;

    /**
     * @ORM\Column(type="boolean")
     */
    private $public;

    /**
     * @ORM\ManyToOne(targetEntity="Location")
     */
    private $location;

    /**
     * @ORM\ManyToOne(targetEntity="EventCategory")
     */
    private $eventCategory;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDateTime;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $cover;

    public function __construct()
    {
        $this->setPublic(true);
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
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlaceName()
    {
        return $this->placeName;
    }

    /**
     * @param mixed $placeName
     */
    public function setPlaceName($placeName)
    {
        $this->placeName = $placeName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * @param mixed $startDateTime
     */
    public function setStartDateTime($startDateTime)
    {
        $this->startDateTime = $startDateTime;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMaximumCapacity()
    {
        return $this->maximumCapacity;
    }

    /**
     * @param mixed $maximumCapacity
     */
    public function setMaximumCapacity($maximumCapacity)
    {
        $this->maximumCapacity = $maximumCapacity;

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

    /**
     * @return boolean
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * @return boolean
     */
    public function isPrivate()
    {
        return !$this->public;
    }

    /**
     * @param boolean $public
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStartDateTimeTimeZone()
    {
        return $this->startDateTimeTimeZone;
    }

    /**
     * @param mixed $startDateTimeTimeZone
     */
    public function setStartDateTimeTimeZone($startDateTimeTimeZone)
    {
        $this->startDateTimeTimeZone = $startDateTimeTimeZone;
    }

    /**
     * @return mixed
     */
    public function getStartDateTimeUTC()
    {
        return $this->startDateTimeUTC;
    }

    /**
     * @param mixed $startDateTimeUTC
     */
    public function setStartDateTimeUTC($startDateTimeUTC)
    {
        $this->startDateTimeUTC = $startDateTimeUTC;
    }

    /**
     * @return mixed
     */
    public function getStartDateTimeUTCOffset()
    {
        return $this->startDateTimeUTCOffset;
    }

    /**
     * @param mixed $startDateTimeUTCOffset
     */
    public function setStartDateTimeUTCOffset($startDateTimeUTCOffset)
    {
        $this->startDateTimeUTCOffset = $startDateTimeUTCOffset;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param mixed $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function getPlaceExternalId()
    {
        if($this->getLocation() instanceof Location) {
            return $this->getLocation()->getExternalId();
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function getEventCategory()
    {
        return $this->eventCategory;
    }

    /**
     * @param mixed $eventCategory
     */
    public function setEventCategory($eventCategory)
    {
        $this->eventCategory = $eventCategory;
    }

    /**
     * @return mixed
     */
    public function getCover()
    {
        return $this->cover;
    }

    /**
     * @param mixed $cover
     */
    public function setCover($cover)
    {
        $this->cover = $cover;
    }
}
