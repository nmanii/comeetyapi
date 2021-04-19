<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 14/07/2017
 * Time: 22:36
 */

namespace AppBundle\Service;


use AppBundle\Entity\Location;

class LocationService
{
    private $googlePlaceService;
    private $googleTimeZoneService;
    private $entityManager;

    public function __construct($entityManager, $googlePlaceService, $googleTimeZoneService)
    {
        $this->googlePlaceService = $googlePlaceService;
        $this->entityManager = $entityManager;
        $this->googleTimeZoneService = $googleTimeZoneService;
    }

    public function getLocationByExternalPlaceId($placeId)
    {
        $locationRepository = $this->entityManager->getRepository('AppBundle:Location');
        $location = $locationRepository->findOneByExternalId($placeId);
        if($location !== null) {
            return $location;
        }

        try {
            //If we didn't find any location with the asked placeId, we grab the data directly from google
            $location = $this->createLocationFromGooglePlaceService($placeId);
        } catch (\Exception $exception) {
            $location = null;
        }
        return $location;
    }

    public function createLocationFromGooglePlaceService($placeId)
    {
        $placeDetails = $this->googlePlaceService->getPlaceDetails($placeId);
        $location = new Location();
        $location->setName($placeDetails['result']['name']);
        $location->setAddress($placeDetails['result']['formatted_address']);

        $location->setLatitude($placeDetails['result']['geometry']['location']['lat']);
        $location->setLongitude($placeDetails['result']['geometry']['location']['lng']);

        $timeZoneDetails = $this->googleTimeZoneService->getCurrentTimeZoneDetails($location->getLatitude(), $location->getLongitude());
        $location->setTimeZone($timeZoneDetails['timeZoneId']);

        $location->setExternalId($placeDetails['result']['place_id']);
        //initialize default value
        $location->setPostalCode('');
        $location->setCity('');
        $location->setCountry('');
        foreach($placeDetails['result']['address_components'] as $addressComponent) {
            if(in_array('country', $addressComponent['types'])) {
                $location->setCountry($addressComponent['long_name']);
                continue;
            }
            if(in_array('postal_code', $addressComponent['types'])) {
                $location->setPostalCode($addressComponent['long_name']);
                continue;
            }
            if(in_array('locality', $addressComponent['types'])) {
                $location->setCity($addressComponent['long_name']);
                continue;
            }
        }
        $this->entityManager->persist($location);
        return $location;
    }

}