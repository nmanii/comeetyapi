<?php

namespace AppBundle\Exception;

class InvalidFormException extends \RuntimeException
{
    private $form;

    public function __construct($message, $form = null) {
        parent::__construct($message);
        $this->form = $form;
    }

    /**
     * @return array|null
     */
    public function getForm()
    {
        return $this->form;
    }
}