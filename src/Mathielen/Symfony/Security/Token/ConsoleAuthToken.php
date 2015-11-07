<?php
namespace Mathielen\Symfony\Security\Token;

use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\User\UserInterface;

class ConsoleAuthToken extends PreAuthenticatedToken
{

    public function __construct(UserInterface $consoleUser, $providerKey='fos_userbundle')
    {
        parent::__construct($consoleUser, '', $providerKey, $consoleUser->getRoles());
    }

}
