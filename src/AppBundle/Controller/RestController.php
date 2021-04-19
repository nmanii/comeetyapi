<?php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RestController extends FOSRestController
{
    protected function getViewAfterSuccessfulCreate($data, $locationHeader)
    {
        $view = $this->view($data, Response::HTTP_CREATED);
        $view->setHeader('Location', $locationHeader);
        return $view;
    }

    protected function getViewAfterSuccessfulUpdate($data)
    {
        $view = $this->view($data, Response::HTTP_OK);
        return $view;
    }

    protected function getViewAfterSuccessfulDelete()
    {
        $view = $this->getNoContentHttpView();
        return $view;
    }

    protected function getManager()
    {
        return $this->getDoctrine()->getManager();
    }

    protected function getNoContentHttpView()
    {
        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    protected function getRepository($repositoryName) {
        return $this->getDoctrine()->getRepository($repositoryName);
    }

    protected function checkIfCanEditData($userId)
    {
        if(!$this->canEditData($userId)) {
            throw new AccessDeniedHttpException();
        }
    }

    protected function canEditData($userId)
    {
        return ($this->getUser()->getId() === (int)$userId);
    }

    protected function canAccessData($userId)
    {
        return ($this->getUser()->getId() === (int)$userId);
    }

    /**
     * Makes response from given exception.
     *
     * @param \Exception $exception
     * @throws BadRequestDataException
     */
    protected function throwFosrestSupportedException(\Exception $exception) {
        if($exception instanceof HttpException) {
            throw $exception;
        } else {
            throw $exception;
            //throw new BadRequestHttpException($exception->getMessage());
        }
    }

    protected function getCurrentDateTimeUTC()
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }

    protected function getLogger() {
        return $this->get('logger');
    }
}