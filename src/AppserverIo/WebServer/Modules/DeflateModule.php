<?php

/**
 * \AppserverIo\WebServer\Modules\DeflateModule
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules;

use AppserverIo\Psr\HttpMessage\RequestInterface;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\WebServer\Interfaces\HttpModuleInterface;
use AppserverIo\Psr\HttpMessage\Protocol;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Server\Dictionaries\ServerVars;

/**
 * Class DeflateModule
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class DeflateModule implements HttpModuleInterface
{

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'deflate';

    /**
     * Defines a list of relevant mime types to be compressed
     *
     * @var array
     */
    protected $relevantMimeTypes = array(
        "text/html",
        "text/plain",
        "text/css",
        "application/json",
        "application/x-javascript",
        "application/javascript",
        "text/xml",
        "application/xml",
        "application/xml+rss",
        "text/javascript",
        "image/svg+xml"
    );

    /**
     * Checks if given mime type is relevant for compression
     *
     * @param string $mimeType The mime type to check
     *
     * @return boolean
     */
    protected function isRelevantMimeType($mimeType)
    {
        // check if given mime type is in relevant mime types list
        return in_array($mimeType, $this->relevantMimeTypes);
    }

    /**
     * Initiates the module
     *
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
        return true;
    }

    /**
     * Implements module logic for given hook
     *
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface          $request        A request object
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface         $response       A response object
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     * @param int                                                    $hook           The current hook to process logic for
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function process(RequestInterface $request, ResponseInterface $response, RequestContextInterface $requestContext, $hook)
    {
        // In php an interface is, by definition, a fixed contract. It is immutable.
        // So we have to declare the right ones afterwards...
        /**
         * @var $request \AppserverIo\Psr\HttpMessage\RequestInterface
         */
        /**
         * @var $response \AppserverIo\Psr\HttpMessage\ResponseInterface
         */

        // if false hook is comming do nothing
        if (ModuleHooks::RESPONSE_PRE !== $hook) {
            return;
        }
        // check if content type header exists if not stop processing
        if (!$response->hasHeader(Protocol::HEADER_CONTENT_TYPE)) {
            return;
        }
        // check if no accept encoding headers are sent
        if (!$request->hasHeader(Protocol::HEADER_ACCEPT_ENCODING)) {
            return;
        }
        // check if response was encoded before and exit than
        if ($response->hasHeader(Protocol::HEADER_CONTENT_ENCODING)) {
            return;
        }
        // do not deflate on proxy requests because proxy servers are responsible for sending correct responses
        if ($requestContext->getServerVar(ServerVars::SERVER_HANDLER) === 'proxy') {
            // stop processing
            return;
        }
        // check if request accepts deflate
        if (strpos($request->getHeader(Protocol::HEADER_ACCEPT_ENCODING), 'deflate') !== false) {
            // get stream meta data
            $streamMetaData = stream_get_meta_data($response->getBodyStream());

            /**
             * Currently it's not possible to apply zlib.deflate filter on memory (php://memory) or
             * temp (php://temp) streams due to a bug in that zlib library.,
             *
             * So for now we'll check if stream type is not MEMORY in case of static files and add
             * deflate filter just for static files served via core module.
             *
             * @link https://bugs.php.net/bug.php?id=48725
             */
            if (($streamMetaData['stream_type'] !== 'MEMORY') && ($this->isRelevantMimeType($response->getHeader(Protocol::HEADER_CONTENT_TYPE)))) {
                // apply encoding filter to response body stream
                stream_filter_append($response->getBodyStream(), 'zlib.deflate', STREAM_FILTER_READ);
                // rewind current body stream
                @rewind($response->getBodyStream());
                // copy body stream to make use of filter in read mode
                $deflateBodyStream = fopen('php://memory', 'w+b');
                // copy stream with appended filter to new deflate body stream
                stream_copy_to_stream($response->getBodyStream(), $deflateBodyStream);
                // reset body stream on response
                $response->setBodyStream($deflateBodyStream);
                // set encoding header info
                $response->addHeader(Protocol::HEADER_CONTENT_ENCODING, 'deflate');
            }
        }
    }

    /**
     * Return's an array of module names which should be executed first
     *
     * @return array The array of module names
     */
    public function getDependencies()
    {
        return array();
    }

    /**
     * Returns the module name
     *
     * @return string The module name
     */
    public function getModuleName()
    {
        return self::MODULE_NAME;
    }

    /**
     * Prepares the module for upcoming request in specific context
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function prepare()
    {
        // nothing to prepare for this module
    }
}
