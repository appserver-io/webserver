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
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Modules;

use TechDivision\Http\HttpProtocol;
use TechDivision\WebServer\ConfigParser\HtaccessParser;
use TechDivision\WebServer\Dictionaries\ServerVars;
use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\WebServer\Interfaces\ModuleInterface;

/**
 * \TechDivision\WebServer\Modules\RewriteModule
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <b.wick@techdivision.com>
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
     * This array will hold all locations (e.g. /example/websocket) we ever encountered in our live time.
     * It will provide a mapping to the $configs array, as several locations can share one config
     * (e.g. a "global" .htaccess or nginx config).
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
     * Initiates the module
     *
     * @param \TechDivision\WebServer\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
        // Prefill the backreferences we got from server context
        $this->fillContextBackreferences($serverContext);

        // Register our dependencies
        $this->dependencies = array(
            'core',
            'directory'
        );

        $this->supportedServerVars = array(
            'headers' => array(
                ServerVars::HTTP_USER_AGENT,
                ServerVars::HTTP_REFERER,
                ServerVars::HTTP_COOKIE,
                ServerVars::HTTP_FORWARDED,
                ServerVars::HTTP_HOST,
                ServerVars::HTTP_PROXY_CONNECTION,
                ServerVars::HTTP_ACCEPT
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
        // Before everything else we collect the pieces of information we need
        $requestedUri = $request->getUri();
        $config = $this->getLocationConfig($requestedUri);
        $engines = $config->getDirectivesByType('TechDivision\WebServer\ConfigParser\Directives\RewriteEngine');
        // We have to check if the engine is even switched on
        if (!empty($engines) && array_pop($engines)->isOn() === false) {

            return;
        }

        // As we are still here it seems the engine is needed, so start collecting the other directives
        $rules = $config->getDirectivesByType('TechDivision\WebServer\ConfigParser\Directives\RewriteRule');
        $conditions = $config->getDirectivesByType('TechDivision\WebServer\ConfigParser\Directives\RewriteCondition');

        // Get the RewriteBase directive
        $bases = $config->getDirectivesByType('TechDivision\WebServer\ConfigParser\Directives\RewriteBase');
        $rewriteBase = array_pop($bases);

        // We have to fill the request part of our $serverBackreferences array here
        $this->fillHeaderBackreferences($request);

        // Some vars come separately, we have to get them nethertheless
        $this->serverBackreferences['%{' . ServerVars::REQUEST_URI . '}'] = $request->getUri();
        $this->serverBackreferences['%{' . ServerVars::DOCUMENT_ROOT . '}'] = $request->getDocumentRoot();
        $this->serverBackreferences['%{' . ServerVars::PATH_INFO . '}'] = $request->getPathInfo();
        $this->serverBackreferences['%{' . ServerVars::REQUEST_METHOD . '}'] = $request->getMethod();
        $this->serverBackreferences['%{' . ServerVars::QUERY_STRING . '}'] = $request->getQueryString();

        // Get the backreferences for all the directives we need
        $backreferences = array_merge(
            $this->serverBackreferences,
            $config->getBackreferences(
                'TechDivision\WebServer\ConfigParser\Directives\RewriteRule',
                array($requestedUri)
            )
        );

        // Iterate over all conditions and perform necessary tasks on them. That would be: resolve, match and get
        // their backreferences
        foreach ($conditions as $key => $condition) {

            // Resolve the condition with the backreferences we got for now
            $condition->resolve($backreferences);

            // If we do not match we will fail right here
            // TODO implement condition flags
            if (!$condition->matches()) {

                return;
            }
        }

        // Now that this condition has been resolved we might get some backreferences our of it
        $backreferences = array_merge(
            $backreferences,
            $config->getBackreferences(
                'TechDivision\WebServer\ConfigParser\Directives\RewriteCondition'
            )
        );

        // Iterate over all rules and perform necessary tasks on them. That would be: resolve, match and if
        // we match we will apply the rule
        $rewrittenUri = $requestedUri;
        foreach ($rules as $key => $rule) {

            // Resolve the rule with the backreferences we got for now
            $rule->resolve($backreferences);

            // If we do not match we will fail right here
            // TODO implement rule flags
            if (!$rule->matches($requestedUri)) {

                return;
            }

            // As we are still here it is save to assume we have to apply this rule
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

            // This will stop processing of the module chain
            return false;

        } elseif (strpos($rewrittenUri, 'http') !== false) {

            // Set the location for our redirect
            $request->addHeader(HttpProtocol::HEADER_LOCATION, $rewrittenUri);

            // This will stop processing of the module chain
            return false;

        } else {
            // Set the URI as we are relative to the original document root
            $rewrittenUri = $rewriteBase . $rewrittenUri;
            $request->setUri($rewrittenUri);
        }
    }

    /**
     * Will return the configuration
     *
     * @param string $uri The requested uri we need the configuration for
     *
     * @return array
     */
    protected function getLocationConfig($uri)
    {
        // We have to check if we already got the config
        if (isset($this->locations[$uri]) && isset($this->configs[$this->locations[$uri]])) {

            $config = $this->configs[$this->locations[$uri]];

            // Is the config recent?
            if ($fileInfo = new \SplFileInfo($config->getConfigPath())) {

                if ($fileInfo->getMTime() == $config->getMTime()) {

                    return $config;
                }
            }
        }

        // As we are still here it is safe to assume that we have to reparse the configuration for this location
        // as there might have been changes
        $configParser = new HtaccessParser();

        // Save the config for later use
        $config = $configParser->getConfigForFile(
            $this->serverBackreferences['%{DOCUMENT_ROOT}'],
            $uri
        );
        $this->locations[$uri] = $config->getConfigPath();
        $this->configs[$config->getConfigPath()] = $config;

        return $config;
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
     * Initiates the module
     *
     * @param \TechDivision\WebServer\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     *
     * @return void
     */
    protected function fillContextBackreferences(ServerContextInterface $serverContext)
    {
        foreach ($serverContext->getServerVars() as $varName => $serverVar) {

            $this->serverBackreferences['%{' . $varName . '}'] = $serverVar;
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
