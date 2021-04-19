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

class UserLanguageController extends RestController
{
    /**
     * REST action which returns all userLanguage
     * Method: GET, url: /users/{$userId}/languages/{$userLanguageId}
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets UserLanguage by userId and languageId",
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
     * @Get("/users/{$userId}/languages/{$userLanguageId}", name="get_user_language")
     */
    public function getUserLanguageAction($userId, $languageId)
    {
        $userLanguageRepository = $this->getRepository('AppBundle:UserLanguage');
        $userLanguages = NULL;

        try {
            $userLanguages = $userLanguageRepository->findOneBy(['id' => $languageId, 'user'=>$userId]);
        } catch (\Exception $exception) {
            $userLanguages = NULL;
        }

        if (!$userLanguages) {
            return $this->getNoContentHttpView();
        }
        return $userLanguages;
    }

    /**
     * REST action which returns all userLanguage
     * Method: GET, url: /users/{userId}/languages
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets all UserLanguage",
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
     * @Get("/users/{userId}/languages", name="get_user_languages")
     */
    public function getUserLanguagesAction($userId)
    {
        $userLanguageRepository = $this->getRepository('AppBundle:UserLanguage');
        $userLanguages = NULL;

        try {
            $userLanguages = $userLanguageRepository->findByUser($userId);
        } catch (\Exception $exception) {
            $userLanguages = NULL;
        }

        if (!$userLanguages) {
            return $this->getNoContentHttpView();
        }
        return $userLanguages;
    }

    /**
     * Update existing UserLanguage from the submitted data or create a new UserLanguage.
     * All required fields must be set within request data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\UserLanguage",
     *   statusCodes = {
     *     201 = "Returned when the UserLanguage is created",
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $userId the userId id
     * @param int $languageId the userLanguage id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when UserLanguage not exist
     *
     * @Put("/users/{userId}/languages/{languageId}")
     */
    public function putUserLanguagesAction(Request $request, $userId, $languageId)
    {
        try {
            try {
                $this->checkIfCanEditData($userId);
                $userLanguageRepository = $this->getRepository('AppBundle:UserLanguage');

                $userLanguage = $userLanguageRepository->findOneBy(['language' => $languageId, 'user'=>$userId]);
                if (!$userLanguage) {
                    $userLanguage = $this->createNewUserLanguage($request->request->all() + ['user' => $userId]);
                    return $this->getViewAfterSuccessfulCreate($userLanguage, $this->generateGetRouteFromUserLanguage($userId, $userLanguage));
                } else {
                    $userLanguage = $this->processForm($userLanguage, $request->request->all() + ['user' => $userId], 'PUT');
                    return $this->getViewAfterSuccessfulUpdate($userLanguage, $this->generateGetRouteFromUserLanguage($userId, $userLanguage));
                }

            } catch (InvalidFormException $exception) {

                return $exception->getForm();
            }
        } catch (\Exception $exception) {
            $this->throwFosrestSupportedException($exception);
        }
    }

    /**
     * Creates new type from request parameters and persists it.
     *
     * @param Request $request
     * @return UserLanguage - persisted type
     */
    protected function createNewUserLanguage($data)
    {
        $userLanguage = new UserLanguage();
        $persistedUserLanguage = $this->processForm($userLanguage, $data, 'POST');
        return $persistedUserLanguage;
    }

    /**
     * Processes the form.
     *
     * @param UserLanguage $userLanguage
     * @param array $parameters
     * @param String $method
     * @return UserLanguage
     *
     * @throws InvalidFormException
     */
    private function processForm(UserLanguage $userLanguage, array $parameters, $method = 'PUT')
    {
        $form = $this->createForm('AppBundle\Form\Type\UserLanguageType', $userLanguage, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {

            $userLanguage = $form->getData();

            $manager = $this->getManager();
            $manager->persist($userLanguage);
            $manager->flush();

            return $userLanguage;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }

    /**
     * REST action which deletes UserLanguage by id.
     * Method: DELETE, url: /user/{userId}/languages/{languageId}
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Deletes a UserLanguage for a given id",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions",
     *     404 = "Returned when the userLanguage is not found"
     *   }
     * )
     *
     * @param Request $request
     * @param $userId
     * @param $userLanguageId
     * @return mixed
     *
     * @Delete("/users/{userId}/languages/{userLanguageId}")
     */
    public function deleteUserLanguageAction(Request $request, $userId, $userLanguageId) {
        $this->checkIfCanEditData($userId);

        $userLanguageRepository = $this->getRepository('AppBundle:UserLanguage');
        $userLanguage = $userLanguageRepository->findOneBy(['id' => $userLanguageId, 'user' => $userId]);

        if ($userLanguage) {
            try {
                $manager = $this->getManager();
                $manager->remove($userLanguage);
                $manager->flush();

                return $this->getViewAfterSuccessfulDelete();
            } catch (\Exception $exception) {
                $this->throwFosrestSupportedException($exception);
            }
        } else {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $userLanguageId));
        }
    }

    /**
     * Update existing UserLanguage from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\UserLanguage",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $id the userLanguage id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when userLanguage does not exist
     *
     * @Patch("/users/{userId}/languages/{userLanguageId}")
     */
    public function patchUserLanguageAction(Request $request, $userId, $userLanguageId) {
        try {
            try {
                $this->checkIfCanEditData($userId);

                $userLanguage = $this->getRepository('AppBundle:UserLanguage')->findOneBy(['id'=> $userLanguageId, 'user' => $userId]);
                if (!$userLanguage) {
                    throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $userLanguageId));
                }
                $userLanguage = $this->processForm($userLanguage, $request->request->all(), 'PATCH');

                return $this->getViewAfterSuccessfulUpdate($userLanguage, $this->generateGetRouteFromUserLanguage($userId, $userLanguage));

            } catch (InvalidFormException $exception) {

                return $exception->getForm();
            }
        } catch (\Exception $exception) {
            $this->throwFosrestSupportedException($exception);
        }
    }

    private function generateGetRouteFromUserLanguage($userId, $userLanguage) {
        return $this->generateUrl('get_user_language', [
            'languageId' => $userLanguage->getId(),
            'userId'  => $userLanguage->getUser()->getId()
        ]);
    }
}
