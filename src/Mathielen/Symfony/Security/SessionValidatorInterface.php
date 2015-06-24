<?php
namespace Mathielen\Symfony\Security;

interface SessionValidatorInterface
{

    public function validate($sessionId);

}
