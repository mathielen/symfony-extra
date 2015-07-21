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
     * @var string
     */
    private $routeFileName;

    /**
     *
     * @param FileLocatorInterface $locator
     */
    public function __construct(FileLocatorInterface $locator, HttpKernelInterface $kernel, $routeFileName='routing.yml')
    {
        parent::__construct($locator);

        $this->routeFileName = $routeFileName;
        $this->kernel = $kernel;
    }

    public function load($file, $type = null)
    {
        $routes = new RouteCollection();

        foreach ($this->kernel->getBundles() as $bundle) {
            $path = $bundle->getPath() . '/Resources/config/' . $this->routeFileName;

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
