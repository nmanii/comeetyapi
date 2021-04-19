<?php

namespace AppBundle\Controller\Billing;

use AppBundle\Entity\Billing\Plan;
use AppBundle\Entity\Billing\Subscription;
use AppBundle\Entity\User;
use AppBundle\Entity\UserConfirmation;
use AppBundle\Entity\UserPasswordResetToken;
use AppBundle\Entity\UserStatistics;
use AppBundle\Service\SubscriptionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Controller\RestController;

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


class SubscriptionController extends RestController
{
    /**
     * @Get("/user/subscription", name="get_current_user_subscription")
     */
    public function getCurrentUserSubscriptionAction(Request $request)
    {
        $user = $this->getUser();
        $subscriptionRepository = $this->getRepository(Subscription::class);
        $subscription = $subscriptionRepository->findOneByUser($user);

        if(!($subscription instanceof Subscription)) {
            throw $this->createNotFoundException('no_active_subscription');
        }

        $view = $this->view($subscription);
        $view->getContext()->addGroups(['Default']);
        return $this->handleView($view);
    }

    /**
     * @Get("/subscriptions/{id}", name="get_subscription")
     */
    public function getSubscriptionAction(Request $request, $id)
    {
        $user = $this->getUser();
        $subscriptionRepository = $this->getRepository(Subscription::class);
        $subscription = $subscriptionRepository->findOneById($id);

        if(!($subscription instanceof Subscription)) {
            throw $this->createNotFoundException('subscription_not_found');
        }

        $view = $this->view($subscription);
        $view->getContext()->addGroups(['Default']);
        return $this->handleView($view);
    }

    /**
     * Create a Subscription from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new Event from the submitted data.",
     *   input = "AppBundle\Entity\Billing\Subscription",
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
     * @Post("/user/subscriptions")
     */
    public function postEventAction(Request $request)
    {
        try {
            try {
                $userId = $this->getUser()->getId();

                $persistedSubscription = $this->createNewSubscription($request->request->all() + ['user' => $userId]);

                return $this->getViewAfterSuccessfulCreate($persistedSubscription, $this->generateGetRouteFromEvent($persistedSubscription));

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
     * @return Event - persisted type
     */
    protected function createNewSubscription($data)
    {
        $subscription = new Subscription();
        $persistedSubscription = $this->processForm($subscription, $data, 'POST');
        return $persistedSubscription;
    }

    /**
     * Processes the form.
     *
     * @param Subscription $event
     * @param array $parameters
     * @param String $method
     * @return Subscription
     *
     * @throws InvalidFormException
     */
    private function processForm(Subscription $subscription, array $parameters, $method = 'PUT')
    {

        $form = $this->createForm('AppBundle\Form\Type\SubscriptionType', $subscription, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {
            $subscription = $form->getData();
            $planName = $form->get('planName')->getData();

            $subscriptionService = $this->get('subscription.service');
            try {
                $subscription = $subscriptionService->createNewUserSubscription($subscription->getUser(), $planName);
            } catch(\Exception $exception) {
                $this->getLogger()->critical($exception);
                throw new UnprocessableEntityHttpException($exception->getMessage());
            }

            return $subscription;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }

    private function generateGetRouteFromEvent($subscription) {
        return $this->generateUrl('get_subscription', [
            'id'  => $subscription->getId()
        ]);
    }
}
