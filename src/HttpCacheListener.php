<?php
namespace ZF\HttpCache;

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

        /* @var $headers Headers */
        $headers = $response->getHeaders();

        $this->setExpires($headers)
            ->setCacheControl($headers)
            ->setPragma($headers)
            ->setVary($headers);
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

        if (! empty($this->config['controllers'])) {
            $cacheConfig = $this->config['controllers'];
        } else {
            $this->cacheConfig = [];

            return;
        }

        $controller = $e->getRouteMatch()
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
            && (! $headers->has('cache-control')
            || ! empty($this->cacheConfig['cache-control']['override']))
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
            and (! $headers->has('expires')
            || ! empty($this->cacheConfig['expires']['override']))
        ) {
            $expires = new Header\Expires();
            try {
                $expires->setDate($this->cacheConfig['expires']['value']);
            } catch (\Zend\Http\Header\Exception\InvalidArgumentException $e) {
                if ($headers->has('date')) {
                    $date = $headers->get('date')->date();
                } else {
                    $date = "@{$_SERVER['REQUEST_TIME']}";
                }
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
            and (! $headers->has('pragma')
            || ! empty($this->cacheConfig['pragma']['override']))
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
            and (! $headers->has('vary')
            || ! empty($this->cacheConfig['vary']['override']))
        ) {
            $vary = new Header\Vary($this->cacheConfig['vary']['value']);
            $headers->addHeader($vary);
        }

        return $this;
    }
}
