<?php
namespace Mathielen\Symfony\Exception;

use FOS\RestBundle\View\ExceptionWrapperHandlerInterface;

class ExceptionWrapperHandler implements ExceptionWrapperHandlerInterface
{

    public function wrap($data)
    {
        return new ExceptionWrapper($data);
    }

}
