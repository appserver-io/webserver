<?php

/**
 * \AppserverIo\WebServer\Modules\Rewrite\Entities\Rule
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2017 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules\Rewrite\Entities;

use AppserverIo\Psr\HttpMessage\Protocol;
use AppserverIo\Http\HttpResponseStates;
use AppserverIo\Psr\HttpMessage\ResponseInterface;
use AppserverIo\WebServer\Modules\Rewrite\Dictionaries\RuleFlags;
use AppserverIo\Server\Dictionaries\ServerVars;
use AppserverIo\Server\Interfaces\RequestContextInterface;

/**
 * AppserverIo\WebServer\Modules\Rewrite\Entities\Rule
 *
 * This class provides an object based representation of a rewrite rule, including logic for testing, applying
 * and handeling conditions.
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2017 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class Rule
{

    /**
     * The allowed values the $type member my assume
     *
     * @var array $allowedTypes
     */
    protected $allowedTypes = array();

    /**
     * The type of rule we have.
     * This might be "relative", "absolute" or "url"
     *
     * @var string $type
     */
    protected $type;

    /**
     * The condition string
     *
     * @var string $conditionString
     */
    protected $conditionString;

    /**
     * The sorted conditions we have to check
     *
     * @var array $sortedConditions
     */
    protected $sortedConditions = array();

    /**
     * Will hold the backreferences of the condition(s) which matched
     *
     * @var array $matchingBackreferences
     */
    protected $matchingBackreferences = array();

    /**
     * Will hold the backreferences of the applied targets
     *
     * @var array $targetBackreferences
     */
    protected $targetBackreferences = array();

    /**
     * The target to rewrite the REDIRECT_URL to
     *
     * @var string $target
     */
    protected $target;

    /**
     * The flag we have to take into consideration when working with the rule
     *
     * @var string $flagString
     */
    protected $flagString;

    /**
     * All flags sorted and brought in relation with their potential parameters
     *
     * @var array $sortedFlags
     */
    protected $sortedFlags;

    /**
     * The default operand we will check all conditions against if none was given explicitly
     *
     * @const string DEFAULT_OPERAND
     */
    const DEFAULT_OPERAND = '@$X_REQUEST_URI';

    /**
     * This constant by which conditions are separated and marked as or-combined
     *
     * @const string CONDITION_OR_DELIMITER
     */
    const CONDITION_OR_DELIMITER = '{OR}';

    /**
     * This constant by which conditions are separated and marked as and-combined (the default)
     *
     * @const string CONDITION_AND_DELIMITER
     */
    const CONDITION_AND_DELIMITER = '{AND}';

    /**
     * This constant is used to separate flags from each other
     *
     * @const string FLAG_DELIMITER
     */
    const FLAG_DELIMITER = ',';

    /**
     * This constant is used to separate flags from their potential parameters
     *
     * @const string FLAG_PARAMETER_ASSIGNER
     */
    const FLAG_PARAMETER_ASSIGNER = '=';

    /**
     * Default constructor
     *
     * @param string       $conditionString Condition string e.g. "^_Resources/.*" or "-f{OR}-d{OR}-d@$REQUEST_FILENAME"
     * @param string|array $target          The target to rewrite to, might be null if we should do nothing
     * @param string       $flagString      A flag string which might be added to to the rule e.g. "L" or "C,R"
     */
    public function __construct($conditionString, $target, $flagString)
    {
        // Set the raw string properties and append our default operand to the condition string
        $this->conditionString = $conditionString;
        $conditionString .= $this->getDefaultOperand();
        $this->target = $target;
        $this->flagString = $flagString;

        // Get the sorted flags, should be easy to break up
        $this->sortedFlags = $this->sortFlags($this->flagString);

        // Set our default values here
        $this->allowedTypes = array(
            'relative',
            'absolute',
            'url'
        );
        $this->matchingBackreferences = array();

        // filter the condition string using our regex, but first of all we will append the default operand
        $conditionPieces = array();
        preg_match_all('`(.*?)@(\$[0-9a-zA-Z_]+)`', $conditionString, $conditionPieces);
        // The first index is always useless, unset it to avoid confusion
        unset($conditionPieces[0]);

        // Conditions are kind of sorted now, we can split them up into condition actions and their operands
        $conditionActions = $conditionPieces[1];
        $conditionOperands = $conditionPieces[2];

        // Iterate over the condition piece arrays, trim them and build our array of sorted condition objects
        for ($i = 0; $i < count($conditionActions); $i ++) {
            // Trim whatever we got here as the string might be a bit dirty
            $actionString = trim($conditionActions[$i], self::CONDITION_OR_DELIMITER . '|' . self::CONDITION_AND_DELIMITER);

            // Collect all and-combined pieces of the conditionstring
            $andActionStringPieces = explode(self::CONDITION_AND_DELIMITER, $actionString);

            // Iterate through them and build up conditions or or-combined condition groups
            foreach ($andActionStringPieces as $andActionStringPiece) {
                // Everything is and-combined (plain array) unless combined otherwise (with a "{OR}" symbol)
                // If we find an or-combination we will make a deeper array within our sorted condition array
                if (strpos($andActionStringPiece, self::CONDITION_OR_DELIMITER) !== false) {
                    // Collect all or-combined conditions into a separate array
                    $actionStringPieces = explode(self::CONDITION_OR_DELIMITER, $andActionStringPiece);

                    // Iterate over the pieces we found and create a condition for each of them
                    $entry = array();
                    foreach ($actionStringPieces as $actionStringPiece) {
                        // Get a new condition instance
                        $entry[] = new Condition($conditionOperands[$i], $actionStringPiece, array_keys($this->sortedFlags));
                    }
                } else {
                    // Get a new condition instance
                    $entry = new Condition($conditionOperands[$i], $andActionStringPiece, array_keys($this->sortedFlags));
                }

                $this->sortedConditions[] = $entry;
            }
        }
    }

    /**
     * Sort the flag string as we will have a bad time parsing the string over and over again
     *
     * @param string $flagString The unsorted string of flags
     *
     * @return array
     */
    protected function sortFlags($flagString)
    {
        // if the flag string is empty we can return an empty array
        if (empty($flagString)) {
            return array();
        }

        $flags = array();
        foreach (explode(self::FLAG_DELIMITER, $flagString) as $flag) {
            $flagPieces = explode(self::FLAG_PARAMETER_ASSIGNER, $flag);

            // Set the pieces (if any)
            if (array_key_exists(1, $flagPieces)) {
                $flags[$flagPieces[0]] = $flagPieces[1];
            } else {
                $flags[$flagPieces[0]] = null;
            }
        }

        return $flags;
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
     * Will resolve the directive's parts by substituting placeholders with the corresponding backreferences
     *
     * @param array $backreferences The backreferences used for resolving placeholders
     *
     * @return void
     */
    public function resolve(array $backreferences)
    {
        // We have to resolve backreferences in three steps.
        // First of all we have to resolve the backreferences based on the server vars
        $this->resolveConditions($backreferences);

        // Second we have to produce the regex based backreferences from the now semi-resolved conditions
        $conditionBackreferences = array_merge($this->getBackreferences(), $backreferences);

        // Last but not least we have to resolve the conditions against the regex backreferences
        $this->resolveConditions($conditionBackreferences);
    }

    /**
     * Will resolve the directive's parts by substituting placeholders with the corresponding backreferences
     *
     * @param array $backreferences The backreferences used for resolving placeholders
     *
     * @return void
     */
    protected function resolveConditions(array $backreferences)
    {
        // Iterate over all conditions and resolve them too
        foreach ($this->sortedConditions as $key => $sortedCondition) {
            // If we got an array we have to iterate over it separately, but be aware they are or-combined
            if (is_array($sortedCondition)) {
                // These are or-combined conditions but we have to resolve them too
                foreach ($sortedCondition as $orKey => $orCombinedCondition) {
                    // Resolve the condition
                    $orCombinedCondition->resolve($backreferences);
                    $this->sortedConditions[$key][$orKey] = $orCombinedCondition;
                }
            } else {
                // Resolve the condition
                $sortedCondition->resolve($backreferences);
                $this->sortedConditions[$key] = $sortedCondition;
            }
        }
    }

    /**
     * Will return true if the rule applies, false if not
     *
     * @return bool
     */
    public function matches()
    {
        // We will iterate over all conditions (and the or-combined condition groups) and if there is a non-matching
        // condition or condition group we will fail
        foreach ($this->sortedConditions as $sortedCondition) {
            // If we got an array we have to iterate over it separately, but be aware they are or-combined
            if (is_array($sortedCondition)) {
                // These are or-combined conditions, so break if we match one
                $orGroupMatched = false;
                foreach ($sortedCondition as $orCombinedCondition) {
                    if ($orCombinedCondition->matches()) {
                        $orGroupMatched = true;
                        $this->matchingBackreferences = array_merge($this->matchingBackreferences, $orCombinedCondition->getBackreferences());
                        break;
                    }
                }

                // Did one condition within this group match?
                if ($orGroupMatched === false) {
                    return false;
                }
            } elseif (! $sortedCondition->matches()) {
                // The single conditions have to match as they are and-combined
                return false;
            } else {
                $this->matchingBackreferences = array_merge($this->matchingBackreferences, $sortedCondition->getBackreferences());
            }
        }

        // We are still here, this sounds good
        return true;
    }

    /**
     * Initiates the module
     *
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext       The request's context
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface         $response             The response instance
     * @param array                                                  $serverBackreferences Server backreferences
     *
     * @throws \InvalidArgumentException
     *
     * @return boolean
     */
    public function apply(RequestContextInterface $requestContext, ResponseInterface $response, array $serverBackreferences)
    {

        // First of all we have to resolve the target string with the backreferences of the matching condition
        // Separate the keys from the values so we can use them in str_replace
        // And also mix in the server's backreferences for good measure
        $this->matchingBackreferences = array_merge($this->matchingBackreferences, $serverBackreferences);
        $backreferenceHolders = array_keys($this->matchingBackreferences);
        $backreferenceValues = array_values($this->matchingBackreferences);

        // If we got a target map (flag "M") we have to resolve the target string we have to use first
        // The following checks will be treated as an additional condition
        if (array_key_exists(RuleFlags::MAP, $this->sortedFlags) && ! empty($this->sortedFlags[RuleFlags::MAP])) {
            // Get our map key for better readability
            $mapKey = str_replace(array_keys($this->matchingBackreferences), $this->matchingBackreferences, $this->sortedFlags[RuleFlags::MAP]);

            // Still here? That sounds good. Get the needed target string now
            if (isset($this->target[$mapKey])) {
                $this->target = $this->target[$mapKey];
            } else {
                // Empty string, we will do nothing with this rule
                $this->target = '';

                // Also clear any L-flag we might find, we could not find what we are looking for so we should not end
                if (array_key_exists(RuleFlags::LAST, $this->sortedFlags)) {
                    unset($this->sortedFlags[RuleFlags::LAST]);
                }
            }
        }

        // Back to our rule...
        // If the target string is empty we do not have to do anything
        if (! empty($this->target)) {
            // Just make sure that you check for the existence of the query string first, as it might not be set
            $queryFreeRequestUri = $requestContext->getServerVar(ServerVars::X_REQUEST_URI);
            if ($requestContext->hasServerVar(ServerVars::QUERY_STRING)) {
                $queryFreeRequestUri = str_replace('?' . $requestContext->getServerVar(ServerVars::QUERY_STRING), '', $queryFreeRequestUri);

                // Set the "redirect" query string as a backup as we might change the original
                $requestContext->setServerVar('REDIRECT_QUERY_STRING', $requestContext->getServerVar(ServerVars::QUERY_STRING));
                $this->targetBackreferences['$REDIRECT_QUERY_STRING'] = $requestContext->getServerVar(ServerVars::QUERY_STRING);
            }
            $requestContext->setServerVar('REDIRECT_URL', $queryFreeRequestUri);
            $this->targetBackreferences['$REDIRECT_URL'] = $queryFreeRequestUri;

            // Substitute the backreferences in our operation
            $this->target = str_replace($backreferenceHolders, $backreferenceValues, $this->target);

            // We have to find out what type of rule we got here
            if (is_readable($this->target)) {
                // We have an absolute file path!
                $this->type = 'absolute';

                // Set the REQUEST_FILENAME path
                $requestContext->setServerVar(ServerVars::REQUEST_FILENAME, $this->target);
                $this->targetBackreferences['$' .  ServerVars::REQUEST_FILENAME] = $this->target;
            } elseif (filter_var($this->target, FILTER_VALIDATE_URL) !== false) {
                // We have a complete URL!
                $this->type = 'url';
            } else {
                // Last but not least we might have gotten a relative path (most likely)
                // Build up the REQUEST_FILENAME from DOCUMENT_ROOT and X_REQUEST_URI (without the query string)
                $this->type = 'relative';

                // Setting the X_REQUEST_URI for internal communication
                // Requested uri always has to begin with a slash
                $this->target = '/' . ltrim($this->target, '/');
                $requestContext->setServerVar(ServerVars::X_REQUEST_URI, $this->target);
                $this->targetBackreferences['$' .  ServerVars::X_REQUEST_URI] = $this->target;

                // Only change the query string if we have one in our target string
                if (strpos($this->target, '?') !== false) {
                    $queryString = substr(strstr($this->target, '?'), 1);
                    $requestContext->setServerVar(ServerVars::QUERY_STRING, $queryString);
                    $this->targetBackreferences['$' .  ServerVars::QUERY_STRING] = $queryString;
                }
            }

            // do we have to make a redirect?
            // if so we have to have to set the status code accordingly and dispatch the response
            if (array_key_exists(RuleFlags::REDIRECT, $this->sortedFlags)) {
                $this->prepareRedirect($requestContext, $response);
            }

            // Lets tell them that we successfully made a redirect
            $requestContext->setServerVar(ServerVars::REDIRECT_STATUS, '200');
            $this->targetBackreferences['$' .  ServerVars::REDIRECT_STATUS] = '200';
        }
        // If we got the "LAST"-flag we have to end here, so return false
        if (array_key_exists(RuleFlags::LAST, $this->sortedFlags)) {
            return false;
        }

        // Still here? That sounds good
        return true;
    }

    /**
     * Will prepare a response for a redirect.
     * This includes setting the new target, the appropriate status code and dispatching it to break the
     * module chain
     *
     * @param \AppserverIo\Server\Interfaces\RequestContextInterface $requestContext The request's context
     * @param \AppserverIo\Psr\HttpMessage\ResponseInterface         $response       The response instance to be prepared
     *
     * @return void
     */
    protected function prepareRedirect($requestContext, ResponseInterface $response)
    {
        // if we got a specific status code we have to filter it and apply it if possible
        $statusCode = 301;
        $proposedStatusCode = $this->sortedFlags[RuleFlags::REDIRECT];
        if (is_numeric($proposedStatusCode) && $proposedStatusCode >= 300 && $proposedStatusCode < 400) {
            $statusCode = $proposedStatusCode;
        }

        // there might be work to be done depending on whether or not we got a complete URL
        if ($this->type === 'relative') {
            $newTarget = $requestContext->getServerVar(ServerVars::REQUEST_SCHEME);
            $newTarget .= '://';
            $newTarget .= $requestContext->getServerVar(ServerVars::HTTP_HOST);
            $this->target = $newTarget . $this->getTarget();
        }

        // set enhance uri to response
        $response->addHeader(Protocol::HEADER_LOCATION, $this->target);
        // send redirect status
        $response->setStatusCode($statusCode);
        // set response state to be dispatched after this without calling other modules process
        $response->setState(HttpResponseStates::DISPATCH);
    }

    /**
     * Will collect all backreferences based on regex typed conditions
     *
     * @return array
     */
    public function getBackreferences()
    {
        // Iterate over all conditions and collect their backreferences
        $backreferences = array();
        foreach ($this->sortedConditions as $key => $sortedCondition) {
            // If we got an array we have to iterate over it separately, but be aware they are or-combined
            if (is_array($sortedCondition)) {
                // These are or-combined conditions but we have to resolve them too
                foreach ($sortedCondition as $orCombinedCondition) {
                    // Get the backreferences of this condition
                    $backreferences = array_merge($backreferences, $orCombinedCondition->getBackreferences());
                }
            } else {
                // Get the backreferences of this condition
                $backreferences = array_merge($backreferences, $sortedCondition->getBackreferences());
            }
        }

        return $backreferences;
    }

    /**
     * Will collect all backreferences based on this rule instance being applied to current server vars
     *
     * @return array
     */
    public function getTargetBackreferences()
    {
        return $this->targetBackreferences;
    }

    /**
     * Getter function for the condition string
     *
     * @return string
     */
    public function getConditionString()
    {
        return $this->conditionString;
    }

    /**
     * Getter function for the flag string
     *
     * @return string
     */
    public function getFlagString()
    {
        return $this->flagString;
    }

    /**
     * Getter function for the target string
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }
}
