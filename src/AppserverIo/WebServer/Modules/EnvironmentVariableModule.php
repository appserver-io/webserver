<?php

/**
 * \AppserverIo\WebServer\Modules\EnvironmentVariableModule
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */

namespace AppserverIo\WebServer\Modules;

use AppserverIo\Psr\HttpMessage\Protocol;
use AppserverIo\Psr\HttpMessage\RequestInterface;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\WebServer\Interfaces\HttpModuleInterface;
use AppserverIo\Server\Dictionaries\ModuleHooks;
use AppserverIo\Server\Exceptions\ModuleException;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Dictionaries\EnvVars;
use AppserverIo\Server\Interfaces\RequestContextInterface;
use AppserverIo\Server\Interfaces\ServerContextInterface;
use AppserverIo\Server\Dictionaries\ModuleVars;

/**
 * Class EnvironmentVariableModule
 *
 * This class implements a module which is able to control server environment variables.
 * These can be conditionally set, unset and copied in form an OS context.
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
class EnvironmentVariableModule implements HttpModuleInterface
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
     * @var \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     */
    protected $serverContext;

    /**
     * The request's context instance
     *
     * @var \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext The request's context instance
     */
    protected $requestContext;

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
     * @param \AppserverIo\Server\Interfaces\ServerContextInterface $serverContext The server's context instance
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
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
                    Protocol::HEADER_STATUS,
                    Protocol::HEADER_DATE,
                    Protocol::HEADER_CONNECTION,
                    Protocol::HEADER_CONNECTION_VALUE_CLOSE,
                    Protocol::HEADER_CONNECTION_VALUE_KEEPALIVE,
                    Protocol::HEADER_CONTENT_TYPE,
                    Protocol::HEADER_CONTENT_DISPOSITION,
                    Protocol::HEADER_CONTENT_LENGTH,
                    Protocol::HEADER_CONTENT_ENCODING,
                    Protocol::HEADER_CACHE_CONTROL,
                    Protocol::HEADER_PRAGMA,
                    Protocol::HEADER_PROXY_CONNECTION,
                    Protocol::HEADER_X_FORWARD,
                    Protocol::HEADER_LAST_MODIFIED,
                    Protocol::HEADER_EXPIRES,
                    Protocol::HEADER_IF_MODIFIED_SINCE,
                    Protocol::HEADER_LOCATION,
                    Protocol::HEADER_X_POWERED_BY,
                    Protocol::HEADER_COOKIE,
                    Protocol::HEADER_SET_COOKIE,
                    Protocol::HEADER_HOST,
                    Protocol::HEADER_ACCEPT,
                    Protocol::HEADER_ACCEPT_CHARSET,
                    Protocol::HEADER_ACCEPT_LANGUAGE,
                    Protocol::HEADER_ACCEPT_ENCODING,
                    Protocol::HEADER_USER_AGENT,
                    Protocol::HEADER_REFERER,
                    Protocol::HEADER_KEEP_ALIVE,
                    Protocol::HEADER_SERVER,
                    Protocol::HEADER_WWW_AUTHENTICATE,
                    Protocol::HEADER_AUTHORIZATION,
                    Protocol::HEADER_X_REQUESTED_WITH,
                    Protocol::HEADER_ACCESS_CONTROL_ALLOW_ORIGIN,
                    Protocol::HEADER_ACCESS_CONTROL_ALLOW_CREDENTIALS,
                    Protocol::STATUS_REASONPHRASE_UNASSIGNED
                )
            );

            $this->supportedSslEnvironmentVars = array(
                EnvVars::HTTPS,
                EnvVars::SSL_PROTOCOL,
                EnvVars::SSL_SESSION_ID,
                EnvVars::SSL_CIPHER,
                EnvVars::SSL_CIPHER_EXPORT,
                EnvVars::SSL_CIPHER_USEKEYSIZE,
                EnvVars::SSL_CIPHER_ALGKEYSIZE,
                EnvVars::SSL_COMPRESS_METHOD,
                EnvVars::SSL_VERSION_INTERFACE,
                EnvVars::SSL_VERSION_LIBRARY,
                EnvVars::SSL_CLIENT_M_VERSION,
                EnvVars::SSL_CLIENT_M_SERIAL,
                EnvVars::SSL_CLIENT_S_DN,
                EnvVars::SSL_CLIENT_S_DN_X509,
                EnvVars::SSL_CLIENT_I_DN,
                EnvVars::SSL_CLIENT_I_DN_X509,
                EnvVars::SSL_CLIENT_V_START,
                EnvVars::SSL_CLIENT_V_END,
                EnvVars::SSL_CLIENT_V_REMAIN,
                EnvVars::SSL_CLIENT_A_SIG,
                EnvVars::SSL_CLIENT_A_KEY,
                EnvVars::SSL_CLIENT_CERT,
                EnvVars::SSL_CLIENT_CERT_CHAIN_N,
                EnvVars::SSL_CLIENT_VERIFY,
                EnvVars::SSL_SERVER_M_VERSION,
                EnvVars::SSL_SERVER_M_SERIAL,
                EnvVars::SSL_SERVER_S_DN,
                EnvVars::SSL_SERVER_S_DN_X509,
                EnvVars::SSL_SERVER_I_DN,
                EnvVars::SSL_SERVER_I_DN_X509,
                EnvVars::SSL_SERVER_V_START,
                EnvVars::SSL_SERVER_V_END,
                EnvVars::SSL_SERVER_A_SIG,
                EnvVars::SSL_SERVER_A_KEY,
                EnvVars::SSL_SERVER_CERT,
                EnvVars::SSL_TLS_SNI
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
     * Return's the request's context instance
     *
     * @return \AppserverIo\Server\Interfaces\RequestContextInterface
     */
    public function getRequestContext()
    {
        return $this->requestContext;
    }

    /**
     * Implement's module logic for given hook
     *
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface          $request        A request object
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface         $response       A response object
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext A requests context instance
     * @param int                                                    $hook           The current hook to process logic for
     *
     * @return bool
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     */
    public function process(
        RequestInterface $request,
        ResponseInterface $response,
        RequestContextInterface $requestContext,
        $hook
    ) {
        // In php an interface is, by definition, a fixed contract. It is immutable.
        // So we have to declair the right ones afterwards...
        /** @var $request \AppserverIo\Psr\HttpMessage\RequestInterface */
        /** @var $response \AppserverIo\Psr\HttpMessage\ResponseInterface */

        // if false hook is comming do nothing
        if (ModuleHooks::REQUEST_POST !== $hook) {
            return;
        }

        // We have to throw a ModuleException on failure, so surround the body with a try...catch block
        try {

            // set request context as member property for further usage
            $this->requestContext = $requestContext;

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
            if ($requestContext->hasModuleVar(ModuleVars::VOLATILE_ENVIRONMENT_VARIABLES)) {

                $volatileEnvironmentVariables = $requestContext->getModuleVar(
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
                            preg_match_all('`(.*?)@(\$[0-9a-zA-Z_\-]+)`', $condition, $conditionPieces);

                            // Check the condition and continue for the next variable if we do not match
                            if (!isset($this->serverBackreferences[$conditionPieces[2][0]])) {

                                continue;
                            }

                            // Do we have a match? Get the potential backreferences
                            $conditionBackreferences = array();
                            if (preg_match(
                                '`' . $conditionPieces[1][0] . '`',
                                $this->serverBackreferences[$conditionPieces[2][0]],
                                $conditionBackreferences
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
                        $possibleBackreferences = array();
                        preg_match('`\$.+?`', $value, $possibleBackreferences);
                        foreach ($possibleBackreferences as $possibleBackreference) {

                            if ($backrefValue = getenv($possibleBackreference)) {
                                // Do we have a backreference which is a server or env var?

                                $value = str_replace($possibleBackreference, $backrefValue, $value);

                            } elseif (isset($conditionBackreferences[(int) substr($possibleBackreference, 1)])) {
                                // We got no backreference from any of the server or env vars, so maybe we got
                                // something from the preg_match
                                $value = str_replace(
                                    $possibleBackreference,
                                    $conditionBackreferences[(int) substr($possibleBackreference, 1)],
                                    $value
                                );
                            }
                        }
                    }

                    // If the value is "null" we will unset the variable
                    if ($value === 'null') {

                        // Unset the variable and continue with the next environment variable
                        if ($requestContext->hasEnvVar($varName)) {

                            $requestContext->unsetEnvVar($varName);
                        }

                        continue;
                    }

                    // Take action according to the needed definition
                    $requestContext->setEnvVar($varName, $value);
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
     * @param \AppserverIo\Psr\HttpMessage\RequestInterface $request The request instance
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
            if (@isset($headerArray[$supportedServerVar])) {
                $this->serverBackreferences['$' . $supportedServerVar] = $headerArray[$supportedServerVar];

                // Also create for the "dynamic" substitution syntax
                $this->serverBackreferences['$' . $supportedServerVar] = $headerArray[$supportedServerVar];
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

            $this->serverBackreferences['$' . $supportedSslEnvironmentVar . ''] = '';
        }
    }

    /**
     * Initiates the module
     *
     * @throws \AppserverIo\Server\Exceptions\ModuleException
     *
     * @return void
     */
    protected function fillContextBackreferences()
    {
        foreach ($this->getRequestContext()->getServerVars() as $varName => $serverVar) {

            // Prefill the value
            $this->serverBackreferences['$' . $varName] = $serverVar;
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
