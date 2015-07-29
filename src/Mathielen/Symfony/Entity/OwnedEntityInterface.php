<?php
namespace Mathielen\Symfony\Entity;

interface OwnedEntityInterface
{

    public function isOwner($owner);

}
