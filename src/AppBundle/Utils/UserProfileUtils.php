<?php
namespace AppBundle\Utils;

use AppBundle\Entity\UserProfile;

class UserProfileUtils extends Controller
{
    public function getGenders()
    {
        return [UserProfile::GENDER_MALE => 'Male', UserProfile::GENDER_FEMALE => 'Female', UserProfile::GENDER_OTHER => 'Other'];
    }

}