<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Entity\UserConfirmation;
use AppBundle\Entity\UserPasswordResetToken;
use AppBundle\Entity\UserStatistics;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Exception\InvalidFormException;
use JMS\Serializer\SerializationContext;


class UserController extends RestController
{
    /**
     * @Get("/user", name="get_current_user")
     */
    public function getCurrentUserAction(Request $request)
    {
        $user = $this->getUser();
        $view = $this->view($user);
        $view->getContext()->addGroups(['Default', 'user_private']);
        return $this->handleView($view);
    }

    /**
     * @Get("/users", name="get_users")
     */
    public function getActiveUsers()
    {
        $userRepository = $this->getRepository('AppBundle:User');

        $users = $userRepository->findActiveWithProfileFilledUsers();

        return $users;
    }

    /**
     * REST action which returns user by id.
     * Method: GET, url: /users/{id}
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a User for a given id",
     *   output = "AppBundle\Entity\User",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/users/{id}", name="get_user")
     */
    public function getUserAction($id)
    {
        $userRepository = $this->getRepository('AppBundle:User');
        $user = NULL;
        try {
            $user = $userRepository->findOneById($id);
        } catch (\Exception $exception) {
            $user = NULL;
        }

        if (!$user) {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $id));
        }
        $view = $this->view($user);
        return $view;
    }

    /**
     * Create a User from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new User from the submitted data.",
     *   input = "AppBundle\Entity\User",
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
     * @Post("/users")
     */
    public function PostUsersAction(Request $request)
    {
        try {
            try {
                $requestData = $request->request->all();
                $inviteInfo = [];
                if(isset($requestData['inviteId'])) {
                    $inviteInfo['id'] = $requestData['inviteId'];
                    unset($requestData['inviteId']);
                }
                if(isset($requestData['inviteToken'])) {
                    $inviteInfo['token'] = $requestData['inviteToken'];
                    unset($requestData['inviteToken']);
                }

                $persistedUser = $this->createNewUser($requestData, $inviteInfo);

                $view =  $this->getViewAfterSuccessfulCreate($persistedUser, $this->generateGetRouteFromUser($persistedUser));
                $view->getContext()->addGroups(['Default', 'user_private']);
                return $view;

            } catch (InvalidFormException $exception) {

                return $exception->getForm();
            }
        } catch (\Exception $exception) {
            $this->throwFosrestSupportedException($exception);
        }
    }

    private function createNewUser($data, $inviteInfo)
    {
        $user = new User();
        $persistedUser = $this->processForm($user, $data, 'POST');

        if(!empty($inviteInfo['id']) && !empty($inviteInfo['token'])) {
            $communityInvitationRepository = $this->getRepository('AppBundle:CommunityInvitation');
            $communityInvitation = $communityInvitationRepository->findOneBy(['id' => $inviteInfo['id'], 'token' => $inviteInfo['token']]);

            if($communityInvitation) {
                $manager = $this->getManager();
                //if the user clicked on the invite link and have the correct token
                $communityInvitation->setRegisteredUser($persistedUser);
                $manager->persist($communityInvitation);
                $manager->flush();
                //if the invite email and token is the same, then we confirmed the user email
                if($communityInvitation->getEmail() === $persistedUser->getEmail()) {
                    $this->finaliseUserConfirmation($persistedUser, $manager);

                }
            }
        }

        if(!$persistedUser->isConfirmed()) {
            $userConfirmation = $this->createNewUserConfirmation($persistedUser);

            $mailManager = $this->get('mail.manager');
            $mailManager->sendEmailConfirmation($persistedUser, $userConfirmation);
        }
        return $persistedUser;
    }

    /**
     * Processes the form.
     *
     * @param User $user
     * @param array $parameters
     * @param String $method
     * @return User
     *
     * @throws InvalidFormException
     */
    private function processForm(User $user, array $parameters, $method = 'PUT')
    {
        $form = $this->createForm('AppBundle\Form\Type\UserType', $user, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {

            $user = $form->getData();
            $user->setPassword($this->get('security.password_encoder')->encodePassword($user, $user->getPlainPassword()));
            $user->setUsername($user->getEmail());
            $user->setActive(true);
            $user->setCreationDateTime($this->getCurrentDateTimeUTC());
            $user->setLevel(user::LEVEL_NEWCOMER);

            try {
                $manager = $this->getManager();
                $manager->persist($user);
                $manager->flush();
            } catch(UniqueConstraintViolationException $ex) {
                throw new ConflictHttpException('email_already_used');
            }

            return $user;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }

    private function createNewUserConfirmation($user)
    {
        $manager = $this->getManager();
        $userConfirmation = new UserConfirmation();
        $date = new \DateTime();
        //Token valid for 48 hour
        $endValidityDateTime = $date->add(new \DateInterval('PT48H'));
        $userConfirmation->setUser($user)
            ->setToken(base64_encode(openssl_random_pseudo_bytes(16)))
            ->setExpirationDateTime($endValidityDateTime);
        $manager->persist($userConfirmation);
        $manager->flush();
        return $userConfirmation;
    }

    /**
     * Update confirmation from the submitted data or create a confirmation
     * All required fields must be set within request data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\User",
     *   statusCodes = {
     *     201 = "Returned when created",
     *     204 = "Returned when successful",
     *     403 = "Returned when the user can not access",
     *     404 = "Returned when the form has errors",
     *     404 = "Returned when the form has errors",
     *     422 = "Returned when token not valid"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $userId the userId id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when User not exist
     * @throws UnprocessableEntityHttpException when token not set or not valid or if token as expired
     *
     * @Put("/users/{userId}/confirmation")
     */
    public function PutUserConfirmationAction(Request $request, $userId)
    {
        $data = $request->request->all();
        if(!array_key_exists('confirmationToken', $data)) {
            throw new UnprocessableEntityHttpException('Confirmation token is not present.');
        }
        $token = $data['confirmationToken'];

        $entityManager = $this->getDoctrine()->getManager();

        $user = $entityManager->getRepository('AppBundle:User')
            ->findOneById($userId);

        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        if($user->isConfirmed()) {
            return $this->view($user, Response::HTTP_NO_CONTENT);
        }

        $userConfirmation = $entityManager->getRepository('AppBundle:UserConfirmation')
            ->findOneBy(['user' => $user]);

        if(!$userConfirmation || $userConfirmation->getToken() != $token) {
            throw new UnprocessableEntityHttpException('Confirmation token is not valid.');
        }

        $this->finaliseUserConfirmation($user, $entityManager);

        $view = $this->view($user, Response::HTTP_CREATED);
        return $view;
    }


    /**
     * Create a new password reset request
     * All required fields must be set within request data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\User",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     403 = "Returned when the user can not access",
     *     404 = "Returned when the form has errors",
     *     404 = "Returned when the form has errors",
     *     422 = "Returned when token not valid"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $userId the userId id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when User not exist
     *
     * @Put("/password-reset-request")
     */
    public function PutUserPasswordResetRequestAction(Request $request)
    {
        $data = $request->request->all();
        if(!array_key_exists('email', $data)) {
            throw new UnprocessableEntityHttpException('empty_email');
        }
        $email = $data['email'];

        $entityManager = $this->getDoctrine()->getManager();

        $user = $entityManager->getRepository('AppBundle:User')
            ->findOneByEmail($email);

        if (!$user) {
            throw new NotFoundHttpException('user_not_found_for_email');
        }

        $passwordResetTokenManager = $this->get('password_reset.token.manager');
        $token = $passwordResetTokenManager->resetAndCreateNewsPasswordToken($user);

        $mailManager = $this->get('mail.manager');
        $mailManager->sendNewPasswordResetToken($user, $token);

        $this->getNoContentHttpView();
    }

    /**
     * Modifiy user password
     * All required fields must be set within request data.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     403 = "Returned when the user can not access",
     *     404 = "Returned when the form has errors",
     *     404 = "Returned when the form has errors",
     *     422 = "Returned when token not valid"
     *   }
     * )
     *
     * @param Request $request the request object
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when User not exist
     *
     * @Put("/user/password")
     */
    public function PutUserPasswordAction(Request $request)
    {
        $data = $request->request->all();
        if(!array_key_exists('token', $data)) {
            throw new UnprocessableEntityHttpException('token_not_present');
        }
        $token = $data['token'];

        if(!array_key_exists('plainPassword', $data)) {
            throw new UnprocessableEntityHttpException('password_not_present');
        }

        if(empty($data['plainPassword'])) {
            throw new UnprocessableEntityHttpException('empty_password');
        }

        $entityManager = $this->getDoctrine()->getManager();

        $passwordResetToken = $entityManager->getRepository('AppBundle:UserPasswordResetToken')
            ->findOneBy(['token' => $token, 'active' => true]);

        if (!$passwordResetToken) {
            throw new NotFoundHttpException('token_not_valid');
        }

        $currentDateTime = $this->getCurrentDateTimeUTC();
        if($passwordResetToken->getExpirationDateTime() < $currentDateTime) {
            throw new UnprocessableEntityHttpException('token_expired');
        }

        $user = $passwordResetToken->getUser();
        $plainPassword = $data['plainPassword'];
        $user->setPassword($this->get('security.password_encoder')->encodePassword($user, $plainPassword));
        $entityManager->persist($user);
        $entityManager->flush();

        //Once the password has been modified, we deactivate all the password reset token for the user
        $passwordResetTokenManager = $this->get('password_reset.token.manager');
        $passwordResetTokenManager->deactivateAllPasswordResetToken($user);

        $mailManager = $this->get('mail.manager');
        $mailManager->sendPasswordModified($user);

        $this->getNoContentHttpView();
    }

    public function generateGetRouteFromUser(User $user)
    {
        return $this->generateUrl('get_user', [
            'id'  => $user->getId()
        ]);
    }

    private function finaliseUserConfirmation($user, $entityManager)
    {
        $user->setConfirmed(true);


        $userStatistics = new UserStatistics();
        $userStatistics->setUser($user);

        $entityManager->persist($userStatistics);
        $entityManager->persist($user);
        $entityManager->flush();

    }
}
