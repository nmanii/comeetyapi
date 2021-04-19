<?php
namespace AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="user_profiles")
 * @ORM\Entity()
 */
class UserProfile
{
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_OTHER = 3;

    /**
     * @ORM\OneToOne(targetEntity="User", inversedBy="profile")
     * @ORM\Id
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", nullable=true, length=191)
     */
    private $lastName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $gender;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $birthDate;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $nativeCountry;

    /**
     * @ORM\Column(type="string", nullable=true, length=191)
     */
    private $description;

    /**
     * @ORM\Column(type="string", nullable=true, length=140)
     */
    private $motto;


    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $discordName;

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param mixed $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * @param mixed $birthDate
     */
    public function setBirthDate($birthDate)
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNativeCountry()
    {
        return $this->nativeCountry;
    }

    /**
     * @param mixed $nativeCountry
     */
    public function setNativeCountry($nativeCountry)
    {
        $this->nativeCountry = $nativeCountry;

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
    public function getMotto()
    {
        return $this->motto;
    }

    /**
     * @param mixed $motto
     */
    public function setMotto($motto)
    {
        $this->motto = $motto;
    }

    /**
     * @return string
     */
    public function getDiscordName()
    {
        return $this->discordName;
    }

    /**
     * @param string $discordName
     */
    public function setDiscordName($discordName)
    {
        $this->discordName = $discordName;
    }
}
