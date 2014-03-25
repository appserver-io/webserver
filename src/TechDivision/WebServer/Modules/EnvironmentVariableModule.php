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
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\WebServer\Modules;

use TechDivision\Http\HttpProtocol;
use TechDivision\WebServer\Exceptions\ModuleException;
use TechDivision\WebServer\Modules\Parser\HtaccessParser;
use TechDivision\WebServer\Dictionaries\ServerVars;
use TechDivision\WebServer\Dictionaries\SslEnvironmentVars;
use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\WebServer\Interfaces\ModuleInterface;
use TechDivision\WebServer\Dictionaries\ModuleVars;

/**
 * TechDivision\WebServer\Modules\EnvironmentVariableModule
 *
 * This class implements a module which is able to control server environment variables.
 * These can be conditionally set, unset and copied in form an OS context.
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class EnvironmentVariableModule implements ModuleInterface
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
     * All variables we have to check (sorted by requested URL)
     *
     * @var array $variables
     */
    protected $variables = array();

    /**
     * The variables as we got it from our basic configuration
     *
     * @var array $configuredVariables
     */
    protected $configuredVariables = array();

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
    const MODULE_NAME = 'environmentVariable';

    /**
     * The default operand we will check all conditions against if none was given explicitly
     *
     * @const string DEFAULT_OPERAND
     */
    const DEFAULT_OPERAND = '@$HTTP_USER_AGENT';

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

            // Get the variables which came from our configuration. There might be more coming from preceding modules
            // which we will load within the process() method.
            $this->configuredVariables = $this->serverContext->getServerConfig()->getEnvironmentVariables();

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

            // Get the environment variables as the array they are within the config.
            // We have to also collect any volative rules which might be set on request base.
            // We might not even get anything, so prepare our rules accordingly
            $volatileEnvironmentVariables = array();
            if ($this->serverContext->hasModuleVar(ModuleVars::VOLATILE_ENVIRONMENT_VARIABLES)) {

                $volatileEnvironmentVariables = $this->serverContext->getModuleVar(
                    ModuleVars::VOLATILE_ENVIRONMENT_VARIABLES
                );
            }

            // Build up the complete ruleset, volatile rules up front
            $variables = array_merge(
                $volatileEnvironmentVariables,
                $this->configuredVariables
            );

            // Only act if we got something
            if (is_array($variables)) {

                // Convert the rules to our internally used objects
                foreach ($variables as $variable) {

                    // Make that condition handling only if there even are conditions
                    if (!empty($variable['condition'])) {

                        // Get the operand
                        $condition = $variable['condition'] . $this->getDefaultOperand();
                        if (strpos($condition, '@') !== false) {

                            // Get the pieces of the condition
                            $conditionPieces = array();
                            preg_match_all('`(.*?)@(\$[0-9a-zA-Z_]+)`', $condition, $conditionPieces);

                            // Check the condition and continue for the next variable if we do not match
                            if (!isset($this->serverBackreferences[$conditionPieces[2][0]])) {

                                continue;
                            }

                            // Do we have a match?
                            if (preg_match(
                                '`' . $conditionPieces[1][0] . '`',
                                $this->serverBackreferences[$conditionPieces[2][0]]
                            )
                                !== 1
                            ) {

                                continue;
                            }
                        }
                    }

                    // We have to split up the definition string, if we do not find a equal character we have to fail
                    if (!strpos($variable['definition'], '=')) {

                        throw new ModuleException('Invalid definition ' . $variable['definition'] . 'missing "=".');
                    }

                    // Get the variable name and its value from the definition string
                    $varName = $this->filterVariableName(strstr($variable['definition'], '=', true));
                    $value = substr(strstr($variable['definition'], '='), 1);

                    // We also have to resolve backreferences for the value part of the definition, as people might want
                    // to pass OS environment vars to the server vars
                    if (strpos($value, '$') !== false) {

                        // Get the possible backreference (might as well be something else) and resolve it if needed
                        // TODO tell them if we do not find a backreference to resolve, might be a problem
                        $possibleBackreference = substr(strstr($value, '$'), 1);
                        if ($possibleBackreference = getenv($possibleBackreference)) {

                            $value = strstr($value, '?', true) . $possibleBackreference;
                        }
                    }

                    // If the value is "null" we will unset the variable
                    if ($value === 'null') {

                        // Unset the variable and continue with the next environment variable
                        if ($this->serverContext->hasServerVar($varName)) {

                            $this->serverContext->unsetServerVar($varName);
                        }

                        continue;
                    }

                    // Take action according to the needed definition
                    $this->serverContext->setServerVar($varName, $value);
                }
            }

        } catch (\Exception $e) {

            // Re-throw as a ModuleException
            throw new ModuleException($e);
        }
    }

    /**
     * Will return the default operand of this action
     *
     * @return string
     */
    protected function getDefaultOperand()
    {
        return self::DEFAULT_OPERAND;
    }

    /**
     * Will fill the header variables into our pre-collected $serverVars array
     *
     * @param \TechDivision\Http\HttpRequestInterface $request The request instance
     *
     * @return void
     */
    protected function fillHeaderBackreferences(
        HttpRequestInterface $request
    ) {
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

    /**
     * Will filter a given var name and sanitize it
     *
     * @param string $varName The name of the variable to filter
     *
     * @return string
     */
    protected function filterVariableName($varName)
    {
        // Strtoupper it for a start
        $tmp = strtoupper($varName);

        // Replace all the characters we do not want with and underscore (as Apache does it)
        $tmp = preg_replace('/[^A-Z0-9_]/', '_', $tmp);

        return $tmp;
    }
}
