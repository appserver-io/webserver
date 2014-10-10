<?php
/**
 * \TechDivision\WebServer\Modules\DeflateModule
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Modules;

use TechDivision\Connection\ConnectionRequestInterface;
use TechDivision\Connection\ConnectionResponseInterface;
use TechDivision\Http\HttpProtocol;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\Server\Dictionaries\ModuleHooks;
use TechDivision\Server\Interfaces\ModuleInterface;
use TechDivision\Server\Exceptions\ModuleException;
use TechDivision\Server\Interfaces\RequestContextInterface;
use TechDivision\Server\Interfaces\ServerContextInterface;

/**
 * Class DeflateModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class DeflateModule implements ModuleInterface
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
        "text/plain",
        "text/css",
        "application/json",
        "application/x-javascript",
        "application/javascript",
        "text/xml",
        "application/xml",
        "application/xml+rss",
        "text/javascript"
    );

    /**
     * Check's if given mime type is relevant for compression
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
     * @param \TechDivision\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
        return true;
    }

    /**
     * Implement's module logic for given hook
     *
     * @param \TechDivision\Connection\ConnectionRequestInterface     $request        A request object
     * @param \TechDivision\Connection\ConnectionResponseInterface    $response       A response object
     * @param \TechDivision\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     * @param int                                                     $hook           The current hook to process logic for
     *
     * @return bool
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function process(
        ConnectionRequestInterface $request,
        ConnectionResponseInterface $response,
        RequestContextInterface $requestContext,
        $hook
    ) {
        // In php an interface is, by definition, a fixed contract. It is immutable.
        // So we have to declair the right ones afterwards...
        /** @var $request \TechDivision\Http\HttpRequestInterface */
        /** @var $response \TechDivision\Http\HttpResponseInterface */

        // if false hook is comming do nothing
        if (ModuleHooks::RESPONSE_PRE !== $hook) {
            return;
        }
        // check if no accept encoding headers are sent
        if (!$request->hasHeader(HttpProtocol::HEADER_ACCEPT_ENCODING)) {
            return;
        }
        // check if response was encoded before and exit than
        if ($response->hasHeader(HttpProtocol::HEADER_CONTENT_ENCODING)) {
            return;
        }
        // check if request accepts deflate
        if (strpos($request->getHeader(HttpProtocol::HEADER_ACCEPT_ENCODING), 'deflate') !== false) {

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
            if (($streamMetaData['stream_type'] !== 'MEMORY')
                && ($this->isRelevantMimeType($response->getHeader(HttpProtocol::HEADER_CONTENT_TYPE)))) {
                // apply encoding filter to response body stream
                stream_filter_append($response->getBodyStream(), 'zlib.deflate', STREAM_FILTER_READ);
                // rewind current body stream
                rewind($response->getBodyStream());
                // copy body stream to make use of filter in read mode
                $deflateBodyStream = fopen('php://memory', 'w+b');
                // copy stream with appended filter to new deflate body stream
                stream_copy_to_stream($response->getBodyStream(), $deflateBodyStream);
                // reset body stream on response
                $response->setBodyStream($deflateBodyStream);
                // set encoding header info
                $response->addHeader(HttpProtocol::HEADER_CONTENT_ENCODING, 'deflate');
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
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function prepare()
    {
        // nothing to prepare for this module
    }
}
