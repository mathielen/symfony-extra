<?php
namespace Mathielen\Symfony\Security\Authorization\Voter;

use Mathielen\Symfony\Entity\Common\OwnedEntity;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OwnedEntityVoter implements VoterInterface
{

    public function supportsAttribute($attribute)
    {
        return true;
    }

    public function supportsClass($class)
    {
        return true;
    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if ($object instanceof OwnedEntity) {
            foreach ($token->getUser()->getCompanies() as $company) {
                if ($object->isOwner($company)) {
                    return VoterInterface::ACCESS_GRANTED;
                }
            }
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}
