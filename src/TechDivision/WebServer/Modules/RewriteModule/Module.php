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
 * TODO A RewriteMap directive would come handy
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

            // Prefill the backreferences we got from server context
            $this->serverContext = $serverContext;
            $this->fillContextBackreferences();

            // Get the rules as the array they are within the config
            // We might not even get anything, so prepare our rules accordingly
            $rules = $this->serverContext->getServerConfig()->getRewrites();
            $this->rules = array();

            // Only act if we got something
            if (is_array($rules)) {

                // Convert the rules to our internally used objects
                foreach ($rules as $rule) {

                    // Add the rule as a Rule object
                    $this->rules[] = new Rule($rule['condition'], $rule['target'], $rule['flag']);
                }
            }

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

            // We have to set the server vars we take care of: SCRIPT_URL and SCRIPT_URI
            $this->setModuleVars($request);

            // Next step, fill the serverBackreferences array
            $this->fillContextBackreferences();
            $this->fillHeaderBackreferences($request);
            $this->fillSslEnvironmentBackreferences();
            
            // Iterate over all rules, resolve vars and apply the rule (if needed)
            foreach ($this->rules as $rule) {

                // Resolve all used backreferences which are NOT linked to the query string.
                // We will resolve query string related backreferences separately as we are not able to cache them
                // as easily as, say, the URI
                $rule->resolve($this->serverBackreferences);

                // Check if the rule matches, and if, apply the rule
                if ($rule->matches()) {

                    $rule->apply();
                }


            }

        } catch (\Exception $e) {

            // Re-throw as a ModuleException
            throw new ModuleException($e);
        }
        /*
               // Before everything else we collect the pieces of information we need
                    $requestedUri = $request->getUri();error_log($requestedUri);
                    $config = $this->getLocationConfig($requestedUri);
                    $options = $config->getDirectivesByType('TechDivision\WebServer\Modules\Parser\Directives\RewriteOptions');

                    // We got the options, check if there are some and react accordingly
                    if (count($options) > 0) {

                        $option = array_pop($options);
                        $option->apply($config, $request);
                    }

                    // As we are still here it seems the engine is needed, so start collecting the other directives
                    $rules = $config->getDirectivesByType('TechDivision\WebServer\Modules\Parser\Directives\RewriteRule');
                    $conditions = $config->getDirectivesByType(
                        'TechDivision\WebServer\Modules\Parser\Directives\RewriteCondition'
                    );

                    // Get the RewriteBase directive
                    $bases = $config->getDirectivesByType('TechDivision\WebServer\Modules\Parser\Directives\RewriteBase');
                    $rewriteBase = array_pop($bases);

                    // We have to fill the request part of our $serverBackreferences array here
                    $this->fillHeaderBackreferences($request);
                    // The server vars seem to change on request too :-(
                    $this->fillContextBackreferences();

                    // Get the backreferences for all the directives we need
                    $backreferences = array_merge(
                        $this->serverBackreferences,
                        $config->getBackreferences(
                            'TechDivision\WebServer\Modules\Parser\Directives\RewriteRule',
                            array($requestedUri)
                        )
                    );

                    // Iterate over all conditions and perform necessary tasks on them. That would be: resolve, match and get
                    // their backreferences
                    foreach ($conditions as $condition) {

                        // Resolve the condition with the backreferences we got for now
                        $condition->resolve($backreferences);

                        // If we do not match we will fail right here
                        // TODO implement condition flags
                        if (!$condition->matches()) {

                            //return;

                        }

                        // If we do not have the "no vary" header we

                        if($condition->isOrCombined()) {
                            // We did succeed but we are or-combined so we do not need work with other conditions

                            break;
                        }
                    }

                    // Now that this condition has been resolved we might get some backreferences our of it
                    $backreferences = array_merge(
                        $backreferences,
                        $config->getBackreferences(
                            'TechDivision\WebServer\Modules\Parser\Directives\RewriteCondition'
                        )
                    );

                    // Iterate over all rules and perform necessary tasks on them. That would be: resolve, match and if
                    // we match we will apply the rule
                    $rewrittenUri = $requestedUri;
                    foreach ($rules as $rule) {

                        // Resolve the rule with the backreferences we got for now
                        $rule->resolve($backreferences);

                        // If we do not match we will fail right here
                        // TODO implement rule flags
                        if (!$rule->matches($requestedUri)) {

                           // return;
                        }

                        // As we are still here it is save to assume we have to apply this rule
                        $rewrittenUri = $rule->apply();
                    }
        error_log($rewrittenUri);
                    // Did we even get something useful? If not then give the other modules a chance
                    if (empty($rewrittenUri)) {

                        return;
                    }

                    // If the URI is an absolute file path we have to dispatch the request here
                    if (is_readable($rewrittenUri)) {

                        // Set the document root to the directory above the referenced file and the uri to the file itself
                        $this->serverContext->setServerVar(ServerVars::DOCUMENT_ROOT, dirname($rewrittenUri));
                        $this->serverContext->setServerVar(ServerVars::REQUEST_URI, basename($rewrittenUri));

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
        */
    }

    /**
     * Will set the additional server vars for the requested URI and URL so it will get preserved
     * over any rewrite.
     *
     * @return void
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
            self::SCRIPT_URI,
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
