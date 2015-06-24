<?php
namespace Mathielen\Symfony\Security;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

class TokenHeaderFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'mathielen.symfony.authentication.provider.tokenheader.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('mathielen.symfony.security.authentication.provider'))
            ->replaceArgument(2, $id)
        ;

        $listenerId = 'mathielen.symfony.authentication.listener.tokenheader.'.$id;
        $listener = $container->setDefinition($listenerId, new DefinitionDecorator('mathielen.symfony.security.authentication.listener'));

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'tokenheader';
    }

    public function addConfiguration(NodeDefinition $node)
    {
    }
}
