<?php
use ZF\HttpCache\HttpCacheListener;
use ZF\HttpCache\HttpCacheListenerFactory;

return [
    'service_manager' => [
        'factories' => [
            HttpCacheListener::class => HttpCacheListenerFactory::class,
        ],
    ],

    'zf-http-cache' => [
    //    'controllers' => [
    //        /*
    //         * Wildcards: in the example below all the responses for the controllers
    //         * and HTTP methods others than those configured will have a
    //         * Cache-control:private header unless a Cache-control header has been
    //         * sent by application, since override is set to false.
    //         *
    //         * All the responses for a non configured HTTP method within a
    //         * configured controller will have the same behavior.
    //         */
    //        '*' => [ // No cache by default
    //    	      '*' => [
    //                'cache-control' => [
    //                    'override' => false,
    //                    'value'    => 'private',
    //                ],
    //            ],
    //        ],
    //
    //        /*
    //         * You can provide a wildcard along with configured HTTP methods within
    //         * a controller configuration in order to configure all non listed HTTP
    //         * methods for this controller.
    //         */
    //        'my-controller-name' => [
    //            'get' => [
    //                'cache-control' => [
    //                    'override' => true,
    //                    'value'    => 'public,
    //                ],
    //            ],
    //            '*' => [
    //                'cache-control' => [
    //                    'override' => false,
    //                    'value'    => 'private',
    //                ],
    //            ],
    //        ],
    //
    //        /*
    //         * Regular configuration.
    //         */
    //        'home' => [ // controller name
    //            'get' => [ // Http method (wildcard '*' supported as whatever method)
    //                /*
    //                 * General HTTP caching theory: http://www.w3.org/Protocols/rfc2616/rfc2616-sec13.html
    //                 * Apache point of view: http://httpd.apache.org/docs/current/mod/mod_proxy.html
    //                 * NginX point of view: http://nginx.org/en/docs/http/ngx_http_proxy_module.html
    //                 * Varnish point of view: https://www.varnish-software.com/static/book/HTTP.html
    //                 */
    //
    //                // Cache-Control: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9
    //                'cache-control' => [
    //                    'override' => true, // Whether to override cache-control header if already present in response, false by default
    //                    'value'    => 'public, must-revalidate, max-age=86400',
    //                ],
    //
    //                // Expires: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.21
    //                // Cache-Control *always* overrides Expires
    //                'expires' => [
    //                    'override' => true, // Whether to override expires header if already present in response
    //                    'value'    => '+1 day', // Can be all format accepted by DateTime::__construct(): http://www.php.net/manual/en/datetime.formats.php
    //                ],
    //
    //                // Pragma: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.32
    //                'pragma' => [
    //                    'override' => true, // Whether to override pragma header if already present in response
    //                    'value'    => '',
    //                ],
    //
    //                // Vary: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.44
    //                'vary' => [
    //                    'override' => true, // Whether to override vary header if already present in response
    //                    'value'    => 'accept-encoding, x-requested-with',
    //                ],
    //            ],
    //        ],
    //    ],
    //
    //    /*
    //     * Whether to enable http cache.
    //     */
    //    'enable' => true,
    //
    //    /*
    //     * Never cache these HTTP status codes.
    //     * Defaults to all others than 200.
    //     */
    //    'http_codes_black_list' => [],
    //
    //    /*
    //     * Delimiter used to mark a controller name as being a regexp.
    //     * If you don't want to use regexps in your config set this
    //     * to null to avoid inutil parsing.
    //     * Regexp wins over wildcard.
    //     */
    //    'regex_delimiter' => '~',
    ],
];
