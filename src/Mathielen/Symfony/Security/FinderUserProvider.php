<?php
namespace Mathielen\Symfony\Security;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

use Symfony\Component\Security\Core\User\UserInterface;

use Infrastructure\Search\Dto\Query;

use Infrastructure\Search\Finder;

use Symfony\Component\Security\Core\User\UserProviderInterface;

class FinderUserProvider implements UserProviderInterface
{

    /**
     * @var Finder
     */
    private $userFinder;

    public function __construct(Finder $userFinder)
    {
        $this->userFinder = $userFinder;
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::loadUserByUsername()
     */
    public function loadUserByUsername($username)
    {
        $query = new Query(array('username' => $username));
        $users = $this->userFinder->search($query);

        if (count($users) > 0) {
            return $users[0];
        } else {
            throw new UsernameNotFoundException("User $username could not be found!");
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::refreshUser()
     */
    public function refreshUser(UserInterface $user)
    {
        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::supportsClass()
     */
    public function supportsClass($class)
    {
        return true; //TODO?
    }
}
