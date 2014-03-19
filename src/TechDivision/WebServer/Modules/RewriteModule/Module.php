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

namespace TechDivision\WebServer\Modules\RewriteModule;

use TechDivision\Http\HttpProtocol;
use TechDivision\WebServer\Exceptions\ModuleException;
use TechDivision\WebServer\Modules\Parser\HtaccessParser;
use TechDivision\WebServer\Dictionaries\ServerVars;
use TechDivision\WebServer\Dictionaries\SslEnvironmentVars;
use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\WebServer\Interfaces\ModuleInterface;

/**
 * \TechDivision\WebServer\Modules\RewriteModule\Module
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 *
 * TODO there currently is no possibility for internal subrequests
 */
class Module implements ModuleInterface
{
    /**
     * Server variables we support and need
     *
     * @var array $supportedServerVars
     */
    protected $supportedServerVars = array();

    /**
     * SSL environment variables we support and need
     *
     * @var array $supportedSslEnvironmentVars
     */
    protected $supportedSslEnvironmentVars = array();

    /**
     * This array will hold all locations (e.g. /example/websocket) we ever encountered in our live time.
     * It will provide a mapping to the $configs array, as several locations can share one config
     * (e.g. a "global" .htaccess or nginx config).
     *
     * @var array<string> $locations
     */
    protected $locations = array();

    /**
     * All rules we have to check
     *
     * @var array $rules
     */
    protected $rules = array();

    /**
     * Will hold all configs we have encountered to be used via the location mapping
     *
     * @var array<\TechDivision\WebServer\Modules\RewriteModule\Config> $configs
     */
    protected $configs = array();

    /**
     * The server's context instance which we preserve for later use
     *
     * @var \TechDivision\WebServer\Interfaces\ServerContextInterface $serverContext $serverContext
     */
    protected $serverContext;

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
     * Defines the SCRIPT_URL constant's name we keep track of
     *
     * @var string
     */
    const SCRIPT_URL = 'SCRIPT_URL';

    /**
     * Defines the SCRIPT_URI constant's name we keep track of
     *
     * @var string
     */
    const SCRIPT_URI = 'SCRIPT_URI';

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
        // We have to throw a ModuleException on failure, so surround the body with a try...catch block
        try {

            // Save the server context for later re-use
            $this->serverContext = $serverContext;

            // Register our dependencies
            $this->dependencies = array(
                'virtualHost'
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

            $this->supportedSslEnvironmentVars = array(
                SslEnvironmentVars::HTTPS,
                SslEnvironmentVars::SSL_PROTOCOL,
                SslEnvironmentVars::SSL_SESSION_ID,
                SslEnvironmentVars::SSL_CIPHER,
                SslEnvironmentVars::SSL_CIPHER_EXPORT,
                SslEnvironmentVars::SSL_CIPHER_USEKEYSIZE,
                SslEnvironmentVars::SSL_CIPHER_ALGKEYSIZE,
                SslEnvironmentVars::SSL_COMPRESS_METHOD,
                SslEnvironmentVars::SSL_VERSION_INTERFACE,
                SslEnvironmentVars::SSL_VERSION_LIBRARY,
                SslEnvironmentVars::SSL_CLIENT_M_VERSION,
                SslEnvironmentVars::SSL_CLIENT_M_SERIAL,
                SslEnvironmentVars::SSL_CLIENT_S_DN,
                SslEnvironmentVars::SSL_CLIENT_S_DN_X509,
                SslEnvironmentVars::SSL_CLIENT_I_DN,
                SslEnvironmentVars::SSL_CLIENT_I_DN_X509,
                SslEnvironmentVars::SSL_CLIENT_V_START,
                SslEnvironmentVars::SSL_CLIENT_V_END,
                SslEnvironmentVars::SSL_CLIENT_V_REMAIN,
                SslEnvironmentVars::SSL_CLIENT_A_SIG,
                SslEnvironmentVars::SSL_CLIENT_A_KEY,
                SslEnvironmentVars::SSL_CLIENT_CERT,
                SslEnvironmentVars::SSL_CLIENT_CERT_CHAIN_N,
                SslEnvironmentVars::SSL_CLIENT_VERIFY,
                SslEnvironmentVars::SSL_SERVER_M_VERSION,
                SslEnvironmentVars::SSL_SERVER_M_SERIAL,
                SslEnvironmentVars::SSL_SERVER_S_DN,
                SslEnvironmentVars::SSL_SERVER_S_DN_X509,
                SslEnvironmentVars::SSL_SERVER_I_DN,
                SslEnvironmentVars::SSL_SERVER_I_DN_X509,
                SslEnvironmentVars::SSL_SERVER_V_START,
                SslEnvironmentVars::SSL_SERVER_V_END,
                SslEnvironmentVars::SSL_SERVER_A_SIG,
                SslEnvironmentVars::SSL_SERVER_A_KEY,
                SslEnvironmentVars::SSL_SERVER_CERT,
                SslEnvironmentVars::SSL_TLS_SNI
            );

        } catch (\Exception $e) {

            // Re-throw as a ModuleException
            throw new ModuleException($e);
        }
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
        // We have to throw a ModuleException on failure, so surround the body with a try...catch block
        try {

            $requestUrl = $this->serverContext->getServerVar(
                ServerVars::HTTP_HOST
            ) . $this->serverContext->getServerVar(ServerVars::REQUEST_URI);

            if (!isset($this->rules[$requestUrl])) {

                // Reset the $serverBackreferences array to avoid mixups of different requests
                $this->serverBackreferences = array();

                // Resolve all used backreferences which are NOT linked to the query string.
                // We will resolve query string related backreferences separately as we are not able to cache them
                // as easily as, say, the URI
                // We also have to resolve all the changes rules in front of us made, so build up the backreferences
                // IN the loop.
                // TODO switch to backreference request not prefill as it might be faster
                $this->fillContextBackreferences();
                $this->fillHeaderBackreferences($request);
                $this->fillSslEnvironmentBackreferences();

                // Get the rules as the array they are within the config
                // We might not even get anything, so prepare our rules accordingly
                $rules = $this->serverContext->getServerConfig()->getRewrites();
                $this->rules[$requestUrl] = array();

                // Only act if we got something
                if (is_array($rules)) {

                    // Convert the rules to our internally used objects
                    foreach ($rules as $rule) {

                        // Add the rule as a Rule object
                        $rule = new Rule($rule['condition'], $rule['target'], $rule['flag']);
                        $rule->resolve($this->serverBackreferences);
                        $this->rules[$requestUrl][] = $rule;
                    }
                }
            }

            // We have to set the server vars we take care of: SCRIPT_URL and SCRIPT_URI
            $this->setModuleVars($request);

            // Iterate over all rules, resolve vars and apply the rule (if needed)
            foreach ($this->rules[$requestUrl] as $rule) {

                // Check if the rule matches, and if, apply the rule
                if ($rule->matches()) {

                    // Apply the rule. If apply() returns false this means this was the last rule to process
                    if ($rule->apply($this->serverContext, $response) === false) {

                        break;
                    }
                }
            }

        } catch (\Exception $e) {

            // Re-throw as a ModuleException
            throw new ModuleException($e);
        }
    }

    /**
     * Will set the additional server vars for the requested URI and URL so it will get preserved
     * over any rewrite.
     *
     * @return void
     *
     * TODO do we need the query string as well?
     */
    protected function setModuleVars()
    {
        // Preserve the requested URI as a backup
        $this->serverContext->setServerVar(
            self::SCRIPT_URI,
            $this->serverContext->getServerVar(ServerVars::REQUEST_URI)
        );

        // Preserve the complete URL as it was requested
        $this->serverContext->setServerVar(
            self::SCRIPT_URL,
            $this->serverContext->getServerVar(ServerVars::SERVER_ADDR) .
            $this->serverContext->getServerVar(ServerVars::REQUEST_URI)
        );
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
            $this->serverBackreferences['$DOCUMENT_ROOT'],
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

        // Iterate over all header vars we know and add them to our serverBackreferences array
        foreach ($this->supportedServerVars['headers'] as $supportedServerVar) {

            // As we got them with another name, we have to rename them, so we will not have to do this on the fly
            $tmp = strtoupper(str_replace('HTTP', 'HEADER', $supportedServerVar));
            if (@isset($headerArray[constant("TechDivision\\Http\\HttpProtocol::$tmp")])) {
                $this->serverBackreferences['$' . $supportedServerVar] = $headerArray[constant(
                    "TechDivision\\Http\\HttpProtocol::$tmp"
                )];

                // Also create for the "dynamic" substitution syntax
                $this->serverBackreferences['$' . constant(
                    "TechDivision\\Http\\HttpProtocol::$tmp"
                )] = $headerArray[constant(
                    "TechDivision\\Http\\HttpProtocol::$tmp"
                )];
            }
        }
    }

    /**
     * Will fill the SSL environment variables into the backreferences.
     * These are empty as long as the SSL module is not loaded.
     *
     * @return void
     *
     * TODO Get this vars from the SSL module as soon as it exists
     */
    protected function fillSslEnvironmentBackreferences()
    {
        // Iterate over all SSL environment variables and fill them into our backreferences
        foreach ($this->supportedSslEnvironmentVars as $supportedSslEnvironmentVar) {

            $this->serverBackreferences['$SSL:' . $supportedSslEnvironmentVar . ''] = '';
        }
    }

    /**
     * Initiates the module
     *
     * @throws \TechDivision\WebServer\Exceptions\ModuleException
     *
     * @return void
     */
    protected function fillContextBackreferences()
    {
        foreach ($this->serverContext->getServerVars() as $varName => $serverVar) {

            // Prefill the value
            $this->serverBackreferences['$' . $varName] = $serverVar;

            // Also create for the "dynamic" substitution syntax
            $this->serverBackreferences['$ENV:' . $varName] = $serverVar;
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
