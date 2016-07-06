<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\HttpCache;

class HttpCacheListenerFactory
{
    /**
     * Factory for producing an HttpCacheListener.
     *
     * Duck-types on the $container type to allow usage with
     * zend-servicemanager versions 2.5+ and 3.0+.
     *
     * @param  \Interop\Container\ContainerInterface|\Zend\ServiceManagerServiceLocatorInterface $container
     * @return HttpCacheListener
     */
    public function __invoke($container)
    {
        $config = [];
        if ($container->has('config')) {
            $config = $container->get('config');
            if (isset($config['zf-http-cache'])) {
                $config = $config['zf-http-cache'];
            }
        }

        $httpCacheListener = new HttpCacheListener($container);
        $httpCacheListener->setConfig($config);

        return $httpCacheListener;
    }
}
