<?php

namespace AppBundle\Controller;

use AppBundle\Entity\CompanyMessageUser;
use AppBundle\Entity\CompanyMessageUserState;
use AppBundle\Exception\InvalidFormException;
use AppBundle\Form\Type\CompanyMessageType;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Delete;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Entity\CompanyMessage;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\DateTime;

class CompanyMessageController extends RestController
{
    /**
     * Create a CompanyMessage from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Create a new CompanyMessage from the submitted data.",
     *   input = "AppBundle\Entity\CompanyMessage",
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
     * @Post("/company/messages")
     */
    public function postCompanyMessageAction(Request $request)
    {
        try {
            try {
                if($this->getUser() != null) {
                    $userId = $this->getUser()->getId();
                } else {
                    $userId = null;
                }

                $persistedCompanyMessage = $this->createNewCompanyMessage($request->request->all() + ['user' => $userId]);

                return $this->getNoContentHttpView();

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
     * @return CompanyMessage - persisted type
     */
    protected function createNewCompanyMessage($data)
    {
        $companyMessage = new CompanyMessage();
        $persistedCompanyMessage = $this->processForm($companyMessage, $data, 'POST');
        return $persistedCompanyMessage;
    }

    /**
     * Processes the form.
     *
     * @param CompanyMessage $companyMessage
     * @param array $parameters
     * @param String $method
     * @return CompanyMessage
     *
     * @throws InvalidFormException
     */
    private function processForm(CompanyMessage $companyMessage, array $parameters, $method = 'PUT')
    {

        $form = $this->createForm('AppBundle\Form\Type\CompanyMessageType', $companyMessage, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {
            $companyMessage = $form->getData();

            $emailData = ['companyMessage' => $companyMessage];
            if($this->getUser() != null ) {
                $emailData += ['userId' => $this->getUser()->getId()];
            }
            $message = \Swift_Message::newInstance()
                ->setSubject('From website: '.$companyMessage->getSubject())
                ->setTo('team@comeety.net')
                ->setFrom($companyMessage->getSenderEmail())
                ->setBody(
                    $this->renderView(
                        'Email/company_message.html.twig',
                        $emailData
                    ),
                    'text/html'
                )
            ;
            $this->get('mailer')->send($message);


            return $companyMessage;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }
}
