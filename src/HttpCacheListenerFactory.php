<?php
namespace ZF\HttpCache;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class HttpCacheListenerFactory implements FactoryInterface
{
    /**
     * @param  ServiceLocatorInterface $services
     * @return HttpCacheListener
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = [];
        if ($serviceLocator->has('Config')) {
            $config = $serviceLocator->get('Config');
            if (isset($config['zf-http-cache'])) {
                $config = $config['zf-http-cache'];
            }
        }

        $httpCacheListener = new HttpCacheListener();
        $httpCacheListener->setConfig($config);

        return $httpCacheListener;
    }
}
