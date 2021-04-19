<?php

namespace AppBundle\Controller;

use AppBundle\Exception\InvalidFormException;
use AppBundle\Form\Type\UserAppNotificationTokenType;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Delete;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Entity\UserAppNotificationToken;

use Symfony\Component\HttpFoundation\Request;

class UserAppNotificationTokenController extends RestController
{

    /**
     * REST action which returns userAppNotificationToken by id.
     * Method: GET, url: /users/{userId}/languages/{id}
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a UserAppNotificationToken for a given id",
     *   output = "AppBundle\Entity\UserAppNotificationToken",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/user/tokens/{token}", name="get_user_token")
     */
    public function getUserAppNotificationTokenAction($id)
    {
        $userAppNotificationTokenRepository = $this->getRepository('AppBundle:UserAppNotificationToken');
        $userAppNotificationToken = NULL;
        try {
            $userAppNotificationToken = $userAppNotificationTokenRepository->findOneByToken($id);
        } catch (\Exception $exception) {
            $userAppNotificationToken = NULL;
        }

        if (!$userAppNotificationToken) {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $id));
        }
        return $userAppNotificationToken;
    }

    /**
     * Create a UserAppNotificationToken from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new UserAppNotificationToken from the submitted data.",
     *   input = "AppBundle\Entity\UserAppNotificationToken",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     *
     * @return FormTypeInterface|View
     *
     * @Post("/user/app_notifications_tokens")
     */
    public function postUserAppNotificationTokenAction(Request $request)
    {
        try {
            try {
                $data = $request->request->all();
                if(!array_key_exists('token', $data)) {
                    throw new UnprocessableEntityHttpException('token is not present.');
                }
                $token = $data['token'];
                $userId = $this->getUser()->getId();

                $persistedUserAppNotificationToken = $this->createNewUserAppNotificationToken($request->request->all() + ['user' => $userId, 'token' => $token]);

                return $this->getViewAfterSuccessfulCreate($persistedUserAppNotificationToken, $this->generateGetRouteFromUserAppNotificationToken($persistedUserAppNotificationToken));

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
     * @return UserAppNotificationToken - persisted type
     */
    protected function createNewUserAppNotificationToken($data)
    {
        $userAppNotificationToken = new UserAppNotificationToken();
        $persistedUserAppNotificationToken = $this->processForm($userAppNotificationToken, $data, 'POST');
        return $persistedUserAppNotificationToken;
    }

    /**
     * Processes the form.
     *
     * @param UserAppNotificationToken $userAppNotificationToken
     * @param array $parameters
     * @param String $method
     * @return UserAppNotificationToken
     *
     * @throws InvalidFormException
     */
    private function processForm(UserAppNotificationToken $userAppNotificationToken, array $parameters, $method = 'PUT')
    {
        $form = $this->createForm('AppBundle\Form\Type\UserAppNotificationTokenType', $userAppNotificationToken, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {

            $userAppNotificationToken = $form->getData();
            $userAppNotificationToken->setCreationDateTime($this->getCurrentDateTimeUTC());

            $manager = $this->getManager();
            $manager->persist($userAppNotificationToken);
            $manager->flush();

            return $userAppNotificationToken;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }

    /**
     * REST action which deletes UserAppNotificationToken by id.
     * Method: DELETE, url: /user/app_notifications_tokens
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Deletes a UserAppNotificationToken for a given id",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions",
     *     404 = "Returned when the userAppNotificationToken is not found"
     *   }
     * )
     *
     * @param Request $request
     * @param $userId
     * @param $userAppNotificationTokenId
     * @return mixed
     *
     * @Delete("/user/app_notifications_tokens")
     */
    public function deleteUserAppNotificationTokenAction(Request $request) {
        $userId = $this->getUser()->getId();

        $data = $request->request->all();
        if(!array_key_exists('token', $data)) {
            throw new UnprocessableEntityHttpException('token is not present.');
        }
        $token = $data['token'];

        $userAppNotificationTokenRepository = $this->getRepository('AppBundle:UserAppNotificationToken');
        $userAppNotificationToken = $userAppNotificationTokenRepository->findOneBy(['token' => $token, 'user' => $userId]);

        if ($userAppNotificationToken) {
            try {
                $manager = $this->getManager();
                $manager->remove($userAppNotificationToken);
                $manager->flush();

                return $this->getViewAfterSuccessfulDelete();
            } catch (\Exception $exception) {
                $this->throwFosrestSupportedException($exception);
            }
        } else {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $token));
        }
    }

    private function generateGetRouteFromUserAppNotificationToken($userAppNotificationToken) {
        return $this->generateUrl('get_user_token', [
            'token' => $userAppNotificationToken->getToken(),
        ]);
    }
}
