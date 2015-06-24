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
        $providerId = 'security.authentication.provider.tokenheader.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('tokenheader.security.authentication.provider'))
            ->replaceArgument(2, $id)
        ;

        $listenerId = 'security.authentication.listener.tokenheader.'.$id;
        $listener = $container->setDefinition($listenerId, new DefinitionDecorator('tokenheader.security.authentication.listener'));

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
