<?php

namespace AppBundle\Controller;

use AppBundle\Entity\CommunityInvitation;
use AppBundle\Exception\InvalidFormException;
use AppBundle\Form\Type\EventType;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Delete;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CommunityInvitationController extends RestController
{
    /**
     * REST action which returns community invitation by id.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a community invitation for a given id",
     *   output = "AppBundle\Entity\CommunityInvitation",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/community_invitation/user", name="get_current_user_community_invitation")
     */
    public function getCommunityInvitationAction(Request $request)
    {
        $communityInvitationRepository = $this->getRepository('AppBundle:CommunityInvitation');
        $communityInvitation = NULL;

        $userId = $this->getUser()->getId();

        try {
            $communityInvitation = $communityInvitationRepository->findOneBy(['registeredUser' => $userId]);
        } catch (\Exception $exception) {
            $communityInvitation = NULL;
        }

        if (!$communityInvitation) {
            throw new NotFoundHttpException();
        }

        return $communityInvitation;
    }

    /**
     * Create a community invitation from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new Community invitation from the submitted data.",
     *   input = "AppBundle\Entity\CommunityInvitation",
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
     * @Post("/community_invitation")
     */
    public function postEventCommunityInvitationAction(Request $request)
    {
        try {
            try {
                $userId = $this->getUser()->getId();

                $persistedCommunityInvitation = $this->createNewCommunityInvitation($request->request->all() + ['sender' => $userId]);

                $mailManager = $this->get('mail.manager');
                $mailManager->sendCommunityInvitation($persistedCommunityInvitation);

                return $this->getViewAfterSuccessfulCreate($persistedCommunityInvitation, $this->generateGetRouteFromCommunityInvitation($persistedCommunityInvitation));

            } catch(UniqueConstraintViolationException $ex) {
                throw new ConflictHttpException('community_invitation_already_sent');
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
     * @return CommunityInvitation - persisted type
     */
    protected function createNewCommunityInvitation($data)
    {
        $communityInvitation = new CommunityInvitation();
        $communityInvitation->setCreationDateTime($this->getCurrentDateTimeUTC());
        $communityInvitation->setToken(bin2hex(random_bytes(10)));

        $persistedCommunityInvitation = $this->processForm($communityInvitation, $data, 'POST');
        return $persistedCommunityInvitation;
    }

    /**
     * Processes the form.
     *
     * @param Event $communityInvitation
     * @param array $parameters
     * @param String $method
     * @return Event
     *
     * @throws InvalidFormException
     */
    private function processForm(CommunityInvitation $communityInvitation, array $parameters, $method = 'PUT')
    {
        $form = $this->createForm('AppBundle\Form\Type\CommunityInvitationType', $communityInvitation, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {
            $communityInvitation = $form->getData();

            $manager = $this->getManager();
            $manager->persist($communityInvitation);
            $manager->flush();

            return $communityInvitation;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }

    private function generateGetRouteFromCommunityInvitation() {
        return $this->generateUrl('get_current_user_community_invitation');
    }
}
