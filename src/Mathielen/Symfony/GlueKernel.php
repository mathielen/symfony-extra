<?php
namespace Mathielen\Symfony;

use Mathielen\Symfony\Config\CumulativeResourceManager;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper;

abstract class GlueKernel extends Kernel
{

    /**
     * {@inheritdoc}
     */
    protected function initializeBundles()
    {
        parent::initializeBundles();

        // pass bundles to CumulativeResourceManager
        $bundles = [];
        foreach ($this->bundles as $name => $bundle) {
            $bundles[$name] = get_class($bundle);
        }
        CumulativeResourceManager::getInstance()->setBundles($bundles);
    }

    /**
     * Get the list of all "autoregistered" bundles
     *
     * @return array List ob bundle objects
     */
    public function registerBundles(array $blackList=[])
    {
        if (!empty($blackList)) {
            $blackList = array_flip($blackList);
            $blackList = array_change_key_case($blackList, CASE_LOWER);
        }

        // clear state of CumulativeResourceManager
        CumulativeResourceManager::getInstance()->clear();

        $bundles = [];

        if (!$this->getCacheDir()) {
            foreach ($this->collectBundles($blackList) as $class => $params) {
                $bundles[] = $params['kernel']
                    ? new $class($this)
                    : new $class;
            }
        } else {
            $file  = $this->getCacheDir() . '/bundles.php';
            $cache = new ConfigCache($file, $this->debug);

            if (!$cache->isFresh($file)) {
                $bundles = $this->collectBundles($blackList);
                $dumper = new PhpBundlesDumper($bundles);

                $metaData = [];
                foreach ($bundles as $bundle) {
                    $metaData[] = new FileResource($bundle['file']);
                }
                $metaData[] = new FileResource($this->rootDir . '/../composer.lock'); //a composer update might add bundles
                $metaData[] = new DirectoryResource($this->rootDir . '/../src/', '/.*Bundle.php$/'); //all bundle.php files

                $cache->write($dumper->dump(), $metaData);
            }

            // require instead of require_once used to correctly handle sub-requests
            $bundles = require ($cache instanceof ConfigCacheInterface)?$cache->getPath():$cache;
        }

        return $bundles;
    }

    /**
     * Finds all bundles in given root folders
     *
     * @param array $roots
     *
     * @return array
     */
    protected function findBundles(array $roots = [], array $blackList = [])
    {
        $classes = [];
        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }
            $root   = realpath($root);
            $dir    = new \RecursiveDirectoryIterator($root, \FilesystemIterator::FOLLOW_SYMLINKS);
            $filter = new \RecursiveCallbackFilterIterator(
                $dir,
                function (\SplFileInfo $current) use (&$classes, $root) {
                    $fileName = strtolower($current->getFilename());
                    if ($fileName === '.'
                        || $fileName === '..'
                        || $fileName === 'tests'
                    ) {
                        return false;
                    }

                    if ($current->isFile() && substr($fileName, -10) === 'bundle.php') {
                        $classname = $this->getClassnameFromFile($current);

                        //check blacklist
                        if (isset($blackList[strtolower(substr($classname, 1))])) {
                            return false;
                        }

                        if (class_exists($classname)) {
                            $reflectionClass = new \ReflectionClass($classname);
                            if ($reflectionClass->implementsInterface('Symfony\Component\HttpKernel\Bundle\BundleInterface') &&
                                !$reflectionClass->isAbstract()
                            ) {
                                $constructor = $reflectionClass->getConstructor();
                                $needsKernel = $constructor && $constructor->getNumberOfRequiredParameters() > 0;
                                $priority = $this->getBundlePriority($reflectionClass);

                                $classes[$classname] = [
                                    'file' => $current->getRealPath(),
                                    'kernel' => $needsKernel,
                                    'priority' => $priority
                                ];
                            }
                        }
                    } elseif ($current->isDir()) {
                        return true;
                    }
                }
            );

            $iterator = new \RecursiveIteratorIterator($filter);
            $iterator->rewind();
        }

        uasort($classes, function ($a, $b) {
            if ($a['priority'] == $b['priority']) {
                return 0;
            }

            return ($a['priority'] < $b['priority']) ? 1 : -1;
        });

        return $classes;
    }

    protected function getBundlePriority(\ReflectionClass $reflectionClass)
    {
        $prio = 0;

        $nsTokens = explode('\\', $reflectionClass->getNamespaceName());

        if ($nsTokens[0] === 'Symfony') {
            $prio += 128;
        } elseif (in_array($nsTokens[0], ['Sensio', 'Doctrine', 'JMS'])) {
            $prio += 64;
        }

        if (isset($nsTokens[2]) && $nsTokens[2] === 'FrameworkBundle') {
            $prio += 8;
        } elseif (isset($nsTokens[2]) && $nsTokens[2] === 'SecurityBundle') {
            $prio += 4;
        }

        return $prio;
    }

    private function getClassnameFromFile($file)
    {
        $fp = fopen($file, 'r');
        $class = $namespace = $buffer = '';
        $i = 0;
        while (!$class) {
            if (feof($fp)) break;

            $buffer .= fread($fp, 512);
            @$tokens = token_get_all($buffer);

            if (strpos($buffer, '{') === false) continue;

            for (;$i<count($tokens);$i++) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j=$i+1;$j<count($tokens); $j++) {
                        if ($tokens[$j][0] === T_STRING) {
                            $namespace .= '\\'.$tokens[$j][1];
                        } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                            break;
                        }
                    }
                }

                if ($tokens[$i][0] === T_CLASS) {
                    for ($j=$i+1;$j<count($tokens);$j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i+2][1];
                        }
                    }
                }
            }
        }

        return $namespace.'\\'.$class;
    }

    /**
     * @return array
     */
    protected function collectBundles(array $blackList)
    {
        $bundles = $this->findBundles([
                $this->getRootDir() . '/../src/',
                $this->getRootDir() . '/../vendor/'
            ], $blackList
        );

        return $bundles;
    }

    /**
     * @param $bundles
     *
     * @return array
     */
    protected function getBundlesMapping(array $bundles, $file)
    {
        $result = [];
        foreach ($bundles as $bundle) {
            $kernel   = false;
            $priority = 0;

            if (is_array($bundle)) {
                $class    = $bundle['name'];
                $kernel   = isset($bundle['kernel']) && true == $bundle['kernel'];
                $priority = isset($bundle['priority']) ? (int) $bundle['priority'] : 0;
            } else {
                $class = $bundle;
            }

            $result[$class] = [
                'name'     => $class,
                'kernel'   => $kernel,
                'priority' => $priority,
                'file'     => $file
            ];
        }

        return $result;
    }

    /**
     * Add custom error handler
     */
    protected function initializeContainer()
    {
        $handler = new ErrorHandler();
        $handler->registerHandlers();

        parent::initializeContainer();
    }

}
