<?php

namespace AppBundle\Controller;

use AppBundle\Exception\InvalidFormException;
use AppBundle\Form\Type\UserLanguageType;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Delete;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Entity\UserLanguage;

use Symfony\Component\HttpFoundation\Request;

class LanguageController extends RestController
{
    /**
     * REST action which returns all userLanguage
     * Method: GET, url: /languages
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets Language list",
     *   output = "AppBundle\Entity\UserLanguage",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/languages", name="get_languages")
     */
    public function getLanguagesAction()
    {
        $languageRepository = $this->getRepository('AppBundle:Language');
        $userLanguages = NULL;

        try {
            $languages = $languageRepository->findAll();
        } catch (\Exception $exception) {
            $languages = NULL;
        }

        if (!$languages) {
            return $this->getNoContentHttpView();
        }
        return $languages;
    }
}
