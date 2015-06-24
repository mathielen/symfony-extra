<?php

namespace Mathielen\Symfony;

use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RouteAutoLoader extends YamlFileLoader
{
    /**
     * @var HttpKernelInterface
     */
    protected $kernel;

    /**
     *
     * @param FileLocatorInterface $locator
     */
    public function __construct(FileLocatorInterface $locator, HttpKernelInterface $kernel)
    {
        parent::__construct($locator);

        $this->kernel = $kernel;
    }

    public function load($file, $type = null)
    {
        $routes = new RouteCollection();

        foreach ($this->kernel->getBundles() as $bundle) {
            $path = $bundle->getPath() . '/Resources/config/routing.yml';

            if (is_file($path)) {
                $routes->addCollection(parent::load($path, $type));
            }
        }

        return $routes;
    }

    public function supports($resource, $type = null)
    {
        return 'glue_auto' == $type;
    }
}