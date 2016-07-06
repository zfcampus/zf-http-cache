<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\HttpCache;

use Interop\Container\ContainerInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\Http\Header;
use Zend\Http\Headers;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;

/**
 * Since caching dynamic or not intended for caching
 * data could be worse than not caching at all,
 * instructions disabling cache should always win.
 */
class HttpCacheListener extends AbstractListenerAggregate
{
    /**
     * @var array
     */
    protected $cacheConfig = [];

    /**
     * @var array
     */
    protected $config = [];

    /** @var ContainerInterface */
    protected $container;

    /**
     * HttpCacheListener constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct($container = null)
    {
        $this->container = $container;
    }

    /**
     * @param EventManagerInterface $events
     * @param int                   $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'onRoute'], -1000);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_FINISH, [$this, 'onResponse'], -1000);
    }

    /**
     * Checks whether to handle this status code.
     *
     * @param  HttpResponse $response
     * @return boolean
     */
    public function checkStatusCode(HttpResponse $response)
    {
        // Only 200 responses are cached by default
        if (empty($this->config['http_codes_black_list'])) {
            return $response->isOk();
        }

        $statusCode = $response->getStatusCode();

        return ! in_array($statusCode, (array) $this->config['http_codes_black_list']);
    }

    /**
     * @return array
     */
    public function getCacheConfig()
    {
        return $this->cacheConfig;
    }

    /**
     * Checks whether there is a config for this HTTP method.
     *
     * @return boolean
     */
    public function hasCacheConfig()
    {
        return ! empty($this->cacheConfig);
    }

    /**
     * @param MvcEvent $e
     */
    public function onResponse(MvcEvent $e)
    {
        if (empty($this->config['enable'])) {
            return;
        }

        /* @var $response HttpResponse */
        $response = $e->getResponse();

        if (! $response instanceof HttpResponse) {
            return;
        }

        if (! $this->checkStatusCode($response)) {
            return;
        }


        /** @var $request HttpRequest */
        $request = $e->getRequest();

        /* @var $headers Headers */
        $headers = $response->getHeaders();

        $this->setExpires($headers)
            ->setETag($request, $response)
            ->setCacheControl($headers)
            ->setPragma($headers)
            ->setVary($headers)
            ->setNotModified($request, $response);
    }

    /**
     * @param MvcEvent $e
     */
    public function onRoute(MvcEvent $e)
    {
        if (empty($this->config['enable'])) {
            return;
        }

        /* @var $request HttpRequest */
        $request = $e->getRequest();
        if (! $request instanceof HttpRequest) {
            return;
        }

        if (empty($this->config['controllers'])) {
            $this->cacheConfig = [];
            return;
        }

        $cacheConfig = $this->config['controllers'];
        $controller  = $e
            ->getRouteMatch()
            ->getParam('controller');

        if (! empty($cacheConfig[$controller])) {
            $controllerConfig = $cacheConfig[$controller];
        } elseif (! empty($this->config['regex_delimiter'])) {
            foreach ($cacheConfig as $key => $config) {
                if (substr($key, 0, 1) === $this->config['regex_delimiter']) {
                    if (preg_match($key, preg_quote($controller, $this->config['regex_delimiter']))) {
                        $controllerConfig = $config;
                        break;
                    }
                }
            }
        } elseif (! empty($cacheConfig['*'])) {
            $controllerConfig = $cacheConfig['*'];
        } else {
            $this->cacheConfig = [];
            return;
        }

        $method = strtolower($request->getMethod());

        if (! empty($controllerConfig[$method])) {
            $methodConfig = $controllerConfig[$method];
        } elseif (! empty($controllerConfig['*'])) {
            $methodConfig = $controllerConfig['*'];
        } elseif (! empty($cacheConfig['*'][$method])) {
            $methodConfig = $cacheConfig['*'][$method];
        } elseif (! empty($cacheConfig['*']['*'])) {
            $methodConfig = $cacheConfig['*']['*'];
        } else {
            $this->cacheConfig = [];
            return;
        }

        $this->cacheConfig = $methodConfig;
    }

    /**
     * Sets cache config.
     *
     * @param  array $config
     * @return self
     */
    public function setCacheConfig(array $cacheConfig)
    {
        $this->cacheConfig = $cacheConfig;
        return $this;
    }

    /**
     * @param  Headers $headers
     * @return self
     */
    public function setCacheControl(Headers $headers)
    {
        if (! empty($this->cacheConfig['cache-control']['value'])
            && (! empty($this->cacheConfig['cache-control']['override'])
                || ! $headers->has('cache-control')
            )
        ) {
            $cacheControl = Header\CacheControl::fromString(
                "Cache-Control: {$this->cacheConfig['cache-control']['value']}"
            );
            $headers->addHeader($cacheControl);
        }

        return $this;
    }

    /**
     * Sets config.
     *
     * @param  array $config
     * @return self
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param  Headers $headers
     * @return self
     */
    public function setExpires(Headers $headers)
    {
        if (! empty($this->cacheConfig['expires']['value'])
            && (! empty($this->cacheConfig['expires']['override'])
                || ! $headers->has('expires')
            )
        ) {
            $expires = new Header\Expires();
            try {
                $expires->setDate($this->cacheConfig['expires']['value']);
            } catch (Header\Exception\InvalidArgumentException $e) {
                $date = $headers->has('date')
                    ? $headers->get('date')->date()
                    : sprintf('@%s', $_SERVER['REQUEST_TIME']);
                $expires->setDate($date);
            }

            $headers->addHeader($expires);
        }

        return $this;
    }

    /**
     * @param  Headers $headers
     * @return self
     */
    public function setPragma(Headers $headers)
    {
        if (! empty($this->cacheConfig['pragma']['value'])
            && (! empty($this->cacheConfig['pragma']['override'])
                || ! $headers->has('pragma')
            )
        ) {
            $pragma = new Header\Pragma($this->cacheConfig['pragma']['value']);
            $headers->addHeader($pragma);
        }

        return $this;
    }

    /**
     * @param  Headers $headers
     * @return self
     */
    public function setVary(Headers $headers)
    {
        if (! empty($this->cacheConfig['vary']['value'])
            && (! empty($this->cacheConfig['vary']['override'])
                || ! $headers->has('vary')
            )
        ) {
            $vary = new Header\Vary($this->cacheConfig['vary']['value']);
            $headers->addHeader($vary);
        }

        return $this;
    }

    /**
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @return $this
     */
    public function setETag(HttpRequest $request, HttpResponse $response)
    {
        $headers = $response->getHeaders();

        if (empty($this->cacheConfig['etag'])) {
            return $this;
        }

        // ETag is already set and we should not override, default is to not overwrite.
        if ($headers->has('Etag')
            && !empty($this->cacheConfig['etag']['override'])
            && $this->cacheConfig['etag']['override'] === false) {

            return $this;
        }

        $generator = $this->getETagGenerator();
        $headers->addHeader(new Header\Etag($generator->generate($request, $response)));

        return $this;
    }

    /**
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @return $this
     */
    public function setNotModified(HttpRequest $request, HttpResponse $response)
    {
        if (!$request->getHeaders()->has('If-None-Match') || !$response->getHeaders()->has('Etag')) {
            return $this;
        }

        $requestEtags = $request->getHeaders()->get('If-None-Match')->getFieldValue();
        $requestEtags = !is_array($requestEtags) ? [$requestEtags] : $requestEtags;
        $responseEtag = $response->getHeaders()->get('Etag')->getFieldValue();

        if (in_array($responseEtag, $requestEtags) || in_array('*', $requestEtags)) {
            $response->setStatusCode(304);
            $response->setContent(null);
        }

        return $this;
    }

    /**
     * Returns an instance of a ETag generator.
     *
     * @return ETagGeneratorInterface
     */
    protected function getETagGenerator()
    {
        // Use custom generator.
        if (!empty($this->container)
            && !empty($this->cacheConfig['etag']['generator'])
            && $this->container->has($this->cacheConfig['etag']['generator'])
            && $this->container->get($this->cacheConfig['etag']['generator']) instanceof EtagGeneratorInterface
        ) {
            return $this->container
                ->get($this->cacheConfig['etag']['generator']);
        }

        return new DefaultETagGenerator();
    }
}
