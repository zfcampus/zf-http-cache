<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\HttpCache;

use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;

class DefaultETagGenerator implements ETagGeneratorInterface
{
    /**
     * Returns an ETag for the given response.
     *
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @return string Etag
     */
    public function generate(HttpRequest $request, HttpResponse $response)
    {
        return md5($response->getContent());
    }
}
