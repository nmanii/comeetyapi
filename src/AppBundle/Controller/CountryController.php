<?php

namespace AppBundle\Controller;

use AppBundle\Exception\InvalidFormException;
use AppBundle\Form\Type\EventType;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Intl\Intl;

use FOS\RestBundle\Controller\Annotations\Get;

use AppBundle\Entity\Event;

use Symfony\Component\HttpFoundation\Request;

class CountryController extends RestController
{

    /**
     * REST action which returns countries
     * Method: GET, url: /countries
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a list of country",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/countries", name="get_countries")
     */
    public function cgetCountryAction()
    {
        $countries = Intl::getRegionBundle()->getCountryNames();

        $countryList = array();
        foreach($countries as $countryCode => $countryName) {
            $countryList[] = ['id' => $countryCode, 'name' => $countryName];
        }
        return $countryList;
    }
}
