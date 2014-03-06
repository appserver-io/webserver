<?php
/**
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
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Modules;

use TechDivision\Http\HttpProtocol;
use TechDivision\WebServer\ConfigParser\HtaccessParser;
use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\WebServer\Interfaces\ModuleInterface;
use TechDivision\WebServer\ConfigParser\Directives\RewriteBase;
use TechDivision\WebServer\ConfigParser\Directives\RewriteCondition;
use TechDivision\WebServer\ConfigParser\Directives\RewriteRule;
use TechDivision\WebServer\ConfigParser\Config;

/**
 * \TechDivision\WebServer\Modules\RewriteModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class RewriteModule implements ModuleInterface
{
    /**
     * @var array $supportedServerVars <TODO FIELD COMMENT>
     */
    protected $supportedServerVars = array();

    /**
     * @var array $conditionAdditionMapping <TODO FIELD COMMENT>
     */
    protected $conditionAdditionMapping = array();

    /**
     * This array will hold all locations (e.g. /example/websocket) we ever encountered in our live time.
     * It will provide a mapping to the $configs array, as several locations can share one config
     * (e.g. a "global" .htaccess).
     *
     * @var array<string> $locations
     */
    protected $locations = array();

    /**
     * Will hold all configs we have encountered to be used via the location mapping
     *
     * @var array<\TechDivision\WebServer\Modules\RewriteModule\Config> $configs
     */
    protected $configs = array();

    /**
     * This array will hold all values which one would suspect as part of the PHP $_SERVER array.
     * As it will be filled from different sources we better keep it as a flat array here so we can
     * easily search for any value we need.
     * Filling and refilling will take place in init() and process() as we need it.
     *
     * @var array $serverVars
     */
    protected $serverBackreferences = array();

    /**
     * @var array $dependencies The modules we depend on
     */
    protected $dependencies = array();

    /**
     * Defines the module name
     *
     * @var string
     */
    const MODULE_NAME = 'rewrite';

    /**
     * Name of local apache like configuration files
     *
     * @var string
     */
    const APACHE_CONF_LOCAL = '.htaccess';

    /**
     * Return's the request instance
     *
     * @return \TechDivision\Http\HttpRequestInterface The request instance
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the response instance
     *
     * @return \TechDivision\Http\HttpResponseInterface The response instance;
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Initiates the module
     *
     * @param \TechDivision\WebServer\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
        $mockConfig = new Config(__FILE__, array(
            new RewriteBase('http://localhost:8586/magento-1.8.1.0/'),
            new RewriteCondition('regex', '%{DOCUMENT_ROOT}/$1', '^.*(www)'),
            new RewriteRule('relative', '/rewritten([0-9]*)([a-z]*)', '/example/?q=$1&m=$2&g=%1')
        ));
        $this->locations['/rewritten123asd'] = $mockConfig;

        // Register our dependencies
        $this->dependencies = array(
            'core',
            'directory'
        );

        $this->supportedServerVars = array(
            'headers' => array(
                'HTTP_USER_AGENT',
                'HTTP_REFERER',
                'HTTP_COOKIE',
                'HTTP_FORWARDED',
                'HTTP_HOST',
                'HTTP_PROXY_CONNECTION',
                'HTTP_ACCEPT'
            )
        );
    }

    /**
     * Implement's module logic
     *
     * @param \TechDivision\Http\HttpRequestInterface  $request  The request instance
     * @param \TechDivision\Http\HttpResponseInterface $response The response instance
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function process(HttpRequestInterface $request, HttpResponseInterface $response)
    {
        $time = microtime(true);

        // We have to fill the request part of our $serverVars array here
        $this->fillHeaderBackreferences($request);
        $this->serverBackreferences['%{DOCUMENT_ROOT}'] = $request->getDocumentRoot();
        $this->serverBackreferences['%{REQUEST_URI}'] = $request->getUri();

        // Save the request URI to save some method calls
        $requestedUri = $request->getUri();

        $config = $this->getLocationConfig($requestedUri);
        $rules = $config->getDirectivesByType('TechDivision\WebServer\ConfigParser\Directives\RewriteRule');
        $conditions = $config->getDirectivesByType('TechDivision\WebServer\ConfigParser\Directives\RewriteCondition');

        // Get the backreferences for all the directives we need
        $backreferences = array_merge(
            $this->serverBackreferences,
            $config->getBackreferences(
                'TechDivision\WebServer\ConfigParser\Directives\RewriteRule',
                array($requestedUri)
            )
        );

        //////////////////////////////////////////////////// resolve cond & check cond &  backref cond

        // We have to replace all $serverVar placeholder within the rewrite conditions we got
        foreach ($conditions as $key => $condition) {

            //////////////////////////////////////////////////// resolve cond

            $condition->resolve($backreferences);

            //////////////////////////////////////////////////// check cond

            // If we do not match we will fail right here
            if (!$condition->matches()) {

                return;
            }
        }

        //////////////////////////////////////////////////// backref cond

        $backreferences = array_merge(
            $backreferences,
            $config->getBackreferences(
                'TechDivision\WebServer\ConfigParser\Directives\RewriteCondition'
            )
        );
        error_log(var_export($backreferences, true));
        //////////////////////////////////////////////////// resolve rules & check rules $ act
        $rewrittenUri = $requestedUri;
        foreach ($rules as $key => $rule) {

            $rule->resolve($backreferences);

            // TODO implement flags
            if (!$rule->matches($requestedUri)) {

                return;
            }

            $rewrittenUri = $rule->apply();
        }

        //////////////////////////////////////////////////// act

        // Did we even get something useful? If not then give the other modules a chance
        if (empty($rewrittenUri)) {

            return;
        }

        // If the URI is an absolute file path we have to dispatch the request here
        if (is_readable($rewrittenUri)) {

            // Set the document root to the directory above the referenced file and the uri to the file itself
            $request->setDocumentRoot(dirname($rewrittenUri));
            $request->setUri(basename($rewrittenUri));

        } elseif (strpos($rewrittenUri, 'http') !== false) {

            // Set the location for our redirect
            $request->addHeader(HttpProtocol::HEADER_LOCATION, $rewrittenUri);

            // This will stop processing of the module chain
            return false;

        } else {
            // Set the URI as we are relative to the original document root

            $request->setUri($rewrittenUri);
        }

        error_log(microtime(true) - $time);
        error_log(var_export($rewrittenUri, true));
    }

    /**
     * Will return the configuration
     *
     * @param $uri
     *
     * @return array
     */
    protected function getLocationConfig($uri)
    {
        // We have to check if we already got the config
        if (isset($this->locations[$uri])) {

            // Is the config recent?
            if ($fileInfo = new \SplFileInfo($this->locations[$uri]->getConfigPath())) {

                if ($fileInfo->getMTime() == $this->locations[$uri]->getMTime()) {

                    return $this->locations[$uri];
                }
            }
        }

        // As we are still here it is safe to assume that we have to reparse the configuration for this location
        // as there might have been changes
        $configParser = new HtaccessParser();

        // Save the config for later use
        $this->locations[$uri] = $configParser->getConfigForFile(
            $this->serverBackreferences['%{DOCUMENT_ROOT}'] . $uri
        );

        return $this->locations[$uri];
    }

    /**
     * Will fill the header variables into our pre-collected $serverVars array
     *
     * @param \TechDivision\Http\HttpRequestInterface $request The request instance
     *
     * @return void
     */
    protected function fillHeaderBackreferences(HttpRequestInterface $request)
    {
        $headerArray = $request->getHeaders();

        foreach ($this->supportedServerVars['headers'] as $supportedServerVar) {

            $tmp = strtoupper(str_replace('HTTP', 'HEADER', $supportedServerVar));
            if (@isset($headerArray[constant("TechDivision\\Http\\HttpProtocol::$tmp")])) {
                $this->serverBackreferences['%{' . $supportedServerVar . '}'] = $headerArray[constant(
                    "TechDivision\\Http\\HttpProtocol::$tmp"
                )];
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
        return $this->dependencies;
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
}
