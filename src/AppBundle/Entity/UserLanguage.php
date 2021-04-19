<?php
namespace AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="user_languages")
 * @UniqueEntity(
 *     fields={"user", "language"},
 *     errorPath="language",
 *     message="This user already has this language configured."
 * )
 * @ORM\Entity()
 *
 */
class UserLanguage
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Language")
     */
    private $language;

    /**
     * @ORM\Column(type="integer")
     * https://www.aventure-linguistique.ch/en/language-level/
     * 1: Beginner (You only know a few words of the language, if that.)
     * 2: Elementary (You can ask a few basic questions and ideas, but with a lot of mistakes.)
     * 3: Medium (you can converse in many situations, with some errors)
     * 4: Fluent (Comfortable in most situations, strong vocabulary, few errors.)
     * 5: Native (You're fluent, pretty much mother tongue. Extremely comfortable, you have complete control over the language.)
     */
    private $level;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    private $user;

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
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param mixed $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
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


}
