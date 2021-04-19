<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\Plan;
use AppBundle\Entity\Billing\Subscription;
use AppBundle\Entity\UserLink;
use AppBundle\Exception\InvalidFormException;
use AppBundle\Form\Type\UserProfileType;
use AppBundle\Service\SubscriptionService;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Delete;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Entity\UserProfile;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class UserProfileController extends RestController
{
    /**
     * Update existing UserProfile from the submitted data or create a new UserProfile.
     * All required fields must be set within request data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\UserProfile",
     *   statusCodes = {
     *     201 = "Returned when the UserProfile is created",
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $userId the userId id
     * @param int $profileId the userProfile id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when UserProfile not exist
     *
     * @Put("/user/profile")
     */
    public function putCurrentUserProfileAction(Request $request)
    {
        try {
            try {
                $userId = $this->getUser()->getId();

                $userProfileRepository = $this->getRepository('AppBundle:UserProfile');
                $userProfile = $userProfileRepository->findOneBy(['user'=>$userId]);
                if (!$userProfile) {
                    $userProfile = $this->createNewUserProfile($request->request->all() + ['user' => $userId]);
                    $mailManager = $this->get('mail.manager');
                    $mailManager->sendWelcome($userProfile);

                    $planRepository = $this->getRepository(Plan::class);
                    $plan = $planRepository->findOneByName('free');
                    $subscriptionService = $this->get('subscription.service');
                    try {
                        $subscriptionService->createSubscriptionForNewUser($userProfile->getUser());
                    } catch (\Exception $exception) {
                        $this->getLogger()->critical($exception);
                        return new UnprocessableEntityHttpException($exception->getMessage());
                    }


                    //Community invitation
                    $communityInvitationRepository = $this->getRepository('AppBundle:CommunityInvitation');
                    $communityInvitation = $communityInvitationRepository->findOneBy(['registeredUser' => $userId]);
                    if(!empty($communityInvitation)) {
                        $manager = $this->getManager();
                        $link = new UserLink();
                        $link->setUser($userProfile->getUser())
                            ->setTargetUser($communityInvitation->getSender())
                            ->setIsCrush(0)
                            ->setType(UserLink::TYPE_FOLLOW)
                            ->setCreationDateTime($this->getCurrentDateTimeUTC());
                        $manager->persist($link);

                        $link2 = new UserLink();
                        $link2->setUser($communityInvitation->getSender())
                            ->setTargetUser($userProfile->getUser())
                            ->setIsCrush(0)
                            ->setType(UserLink::TYPE_FOLLOW)
                            ->setCreationDateTime($this->getCurrentDateTimeUTC());
                        $manager->persist($link2);

                        $manager->flush();
                    }

                    return $this->getViewAfterSuccessfulCreate($userProfile, $this->generateGetRouteFromUserProfile($userProfile));
                } else {
                    $userProfile = $this->processForm($userProfile, $request->request->all() + ['user' => $userId], 'PUT');
                    return $this->getViewAfterSuccessfulUpdate($userProfile, $this->generateGetRouteFromUserProfile($userProfile));
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
     * @return UserProfile - persisted type
     */
    protected function createNewUserProfile($data)
    {
        $userProfile = new UserProfile();
        $persistedUserProfile = $this->processForm($userProfile, $data, 'POST');
        return $persistedUserProfile;
    }

    /**
     * Processes the form.
     *
     * @param UserProfile $userProfile
     * @param array $parameters
     * @param String $method
     * @return UserProfile
     *
     * @throws InvalidFormException
     */
    private function processForm(UserProfile $userProfile, array $parameters, $method = 'PUT')
    {
        $form = $this->createForm('AppBundle\Form\Type\UserProfileType', $userProfile, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {

            $userProfile = $form->getData();

            $manager = $this->getManager();
            $manager->persist($userProfile);
            $manager->flush();

            return $userProfile;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }

    /**
     * REST action which deletes UserProfile by id.
     * Method: DELETE, url: /user/{userId}/profiles/{profileId}
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Deletes a UserProfile for a given id",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions",
     *     404 = "Returned when the userProfile is not found"
     *   }
     * )
     *
     * @param Request $request
     * @param $userId
     * @param $userProfileId
     * @return mixed
     *
     * @Delete("/users/{userId}/profile")
     */
    public function deleteUserProfileAction(Request $request, $userId) {
        $this->checkIfCanEditData($userId);

        $userProfileRepository = $this->getRepository('AppBundle:UserProfile');
        $userProfile = $userProfileRepository->findOneBy(['user' => $userId]);

        if ($userProfile) {
            try {
                $manager = $this->getManager();
                $manager->remove($userProfile);
                $manager->flush();

                return $this->getViewAfterSuccessfulDelete();
            } catch (\Exception $exception) {
                $this->throwFosrestSupportedException($exception);
            }
        } else {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $userId));
        }
    }

    /**
     * Update existing UserProfile from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\UserProfile",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $id the userProfile id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when userProfile does not exist
     *
     * @Patch("/users/{userId}/profile")
     */
    public function patchUserProfileAction(Request $request, $userId) {
        try {
            try {
                $this->checkIfCanEditData($userId);

                $userProfile = $this->getRepository('AppBundle:UserProfile')->findOneBy(['user' => $userId]);
                if (!$userProfile) {
                    throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $userId));
                }
                $userProfile = $this->processForm($userProfile, $request->request->all(), 'PATCH');

                return $this->getViewAfterSuccessfulUpdate($userProfile, $this->generateGetRouteFromUserProfile($userProfile));

            } catch (InvalidFormException $exception) {

                return $exception->getForm();
            }
        } catch (\Exception $exception) {
            $this->throwFosrestSupportedException($exception);
        }
    }

    /**
     * Update the discordName in an existing UserProfile from the submitted data and send an email to admin to manage the discord
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\UserProfile",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $id the userProfile id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when userProfile does not exist
     *
     * @Patch("/users/{userId}/profile/discordname")
     */
    public function patchDiscordNameAction(Request $request, $userId) {
        try {
            try {
                $this->checkIfCanEditData($userId);

                $userProfile = $this->getRepository('AppBundle:UserProfile')->findOneBy(['user' => $userId]);

                if (!$userProfile) {
                    throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $userId));
                }

                if(!$userProfile->getUser()->isActive() || !$userProfile->getUser()->isConfirmed()) {
                    throw new UnprocessableEntityHttpException(sprintf('The resource \'%s\' is not active or confirmed.', $userId));
                }

                $oldDiscordName = $userProfile->getDiscordName();

                $userProfile = $this->processForm($userProfile, $request->request->all(), 'PATCH');

                if($oldDiscordName !== $userProfile->getDiscordName()) {
                    $mailManager = $this->get('mail.manager');
                    $mailManager->sendDiscordNameChange($userProfile, $oldDiscordName);
                }

                return $this->getViewAfterSuccessfulUpdate($userProfile, $this->generateGetRouteFromUserProfile($userProfile));

            } catch (InvalidFormException $exception) {

                return $exception->getForm();
            }
        } catch (\Exception $exception) {
            $this->throwFosrestSupportedException($exception);
        }
    }

    private function generateGetRouteFromUserProfile($userProfile) {
        return $this->generateUrl('get_current_user');
    }
}
