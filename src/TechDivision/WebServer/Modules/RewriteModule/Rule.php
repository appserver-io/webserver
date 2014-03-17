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
 * @category   Php-by-contract
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\WebServer\Modules\RewriteModule;

use TechDivision\Http\HttpProtocol;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\Http\HttpResponseStates;
use TechDivision\WebServer\Dictionaries\ServerVars;
use TechDivision\WebServer\Interfaces\ServerContextInterface;

/**
 * TechDivision\WebServer\Modules\RewriteModule\Rule
 *
 * <TODO CLASS DESCRIPTION>
 *
 * @category   Php-by-contract
 * @package    TechDivision_WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
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
     * The type of rule we have. This might be "relative", "absolute" or "url"
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
     * The target to rewrite the REDIRECT_URL to
     *
     * @var string $targetString
     */
    protected $targetString;

    /**
     * The flag we have to take into consideration when working with the rule
     *
     * @var string $flagString
     */
    protected $flagString;

    /**
     * The default operand we will check all conditions against if none was given explicitly
     *
     * @const string DEFAULT_OPERAND
     */
    const DEFAULT_OPERAND = '@$REQUEST_URI';

    /**
     * This constant by which conditions are separated and marked as or-combined
     *
     * @const string CONDITION_OR_DELIMETER
     */
    const CONDITION_OR_DELIMETER = '|';

    /**
     * This constant by which conditions are separated and marked as and-combined (the default)
     *
     * @const string CONDITION_AND_DELIMETER
     */
    const CONDITION_AND_DELIMETER = ',';

    /**
     * Default constructor
     *
     * @param string $conditionString The condition string e.g. "^_Resources/.*" or "-f|-d|-d@$REQUEST_FILENAME"
     * @param string $targetString    The target to rewrite to, might be null if we should do nothing
     * @param string $flagString      A flag string which might be added to to the rule e.g. "L" or "C,R"
     */
    public function __construct($conditionString, $targetString, $flagString)
    {
        // Set the raw string properties and append our default operand to the condition string
        $this->conditionString = $conditionString = $conditionString . self::DEFAULT_OPERAND;
        $this->targetString = $targetString;
        $this->flagString = $flagString;

        // Set our default values here
        $this->allowedTypes = array('relative', 'absolute', 'url');

        // filter the condition string using our regex, but first of all we will append the default operand
        $conditionPieces = array();
        preg_match_all('`(.*?)@(\$[0-9a-zA-Z_]+)`', $conditionString, $conditionPieces);
        // The first index is always useless, unset it to avoid confusion
        unset($conditionPieces[0]);

        // Conditions are kind of sorted now, we can split them up into condition actions and their operands
        $conditionActions = $conditionPieces[1];
        $conditionOperands = $conditionPieces[2];

        // Iterate over the condition piece arrays, trim them and build our array of sorted condition objects
        for ($i = 0; $i < count($conditionActions); $i++) {

            // Trim whatever we got here as the string might be a bit dirty
            $actionString = trim($conditionActions[$i], '\||,');

            // Everything is and-combined (plain array) unless combined otherwise (with a "|" symbol)
            // If we find an or-combination we will make a deeper array within our sorted condition array
            if (strpos($actionString, self::CONDITION_OR_DELIMETER) !== false) {

                // Collect all or-combined conditions into a separate array
                $actionStringPieces = explode(self::CONDITION_OR_DELIMETER, $actionString);

                // Iterate over the pieces we found and create a condition for each of them
                $entry = array();
                foreach ($actionStringPieces as $actionStringPiece) {

                    // Get a new condition instance
                    $entry[] = new Condition($conditionOperands[$i], $actionStringPiece);
                }

            } else {

                // Get a new condition instance
                $entry = new Condition($conditionOperands[$i], $actionString);
            }

            $this->sortedConditions[] = $entry;
        }
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
        $conditionBackreferences = $this->getBackreferences();

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
        // Separate the keys from the values so we can use them in str_replace
        $backreferenceHolders = array_keys($backreferences);
        $backreferenceValues = array_values($backreferences);

        // Substitute the backreferences in our operation
        $this->targetString = str_replace($backreferenceHolders, $backreferenceValues, $this->targetString);

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
                        break;
                    }
                }

                // Did one condition within this group match?
                if ($orGroupMatched === false) {

                    return false;
                }
            } elseif (!$sortedCondition->matches()) {
                // The single conditions have to match as they are and-combined
                return false;
            }
        }

        // We are still here, this sounds good
        return true;
    }

    /**
     * Initiates the module
     *
     * @param \TechDivision\WebServer\Interfaces\ServerContextInterface $serverContext The server's context instance
     * @param \TechDivision\Http\HttpResponseInterface                  $response      The response instance
     *
     * @return boolean
     */
    public function apply(
        ServerContextInterface $serverContext,
        HttpResponseInterface $response
    ) {
        $serverContext->setServerVar('REDIRECT_STATUS', '200');
        // Just make sure that you check for the existence of the query string first, as it might not be set
        $queryFreeRequestUri = $serverContext->getServerVar(ServerVars::REQUEST_URI);
        if ($serverContext->hasServerVar(ServerVars::QUERY_STRING)) {

            $queryFreeRequestUri = str_replace(
                '?' . $serverContext->getServerVar(ServerVars::QUERY_STRING),
                '',
                $queryFreeRequestUri
            );
        }

        $serverContext->setServerVar('REDIRECT_URL', $queryFreeRequestUri);

        // Back to our rule...
        // If the target string is empty we do not have to do anything
        if (!empty($this->targetString)) {

            // We have to find out what type of rule we got here
            if (is_readable($this->targetString)) {

                // We have an absolute file path!
                $this->type = 'absolute';

                // Set the REQUEST_FILENAME path
                $serverContext->setServerVar(ServerVars::REQUEST_FILENAME, $this->targetString);

            } elseif (filter_var($this->targetString, FILTER_VALIDATE_URL) && strpos(
                $this->flagString,
                'R'
            ) !== false
            ) {
                // We were passed a valid URL and should redirect to it
                // TODO implement better flag handling

                $serverContext->setServerVar('REDIRECT_URL', $this->targetString);

                // set enhance uri to response
                $response->addHeader(HttpProtocol::HEADER_LOCATION, $this->targetString);
                // send redirect status
                $response->setStatusCode(301);
                // set response state to be dispatched after this without calling other modules process
                $response->setState(HttpResponseStates::DISPATCH);

            } else {
                // Last but not least we might have gotten a relative path (most likely)
                // Build up the REQUEST_FILENAME from DOCUMENT_ROOT and REQUEST_URI (without the query string)
                $serverContext->setServerVar(
                    ServerVars::SCRIPT_FILENAME,
                    $serverContext->getServerVar(ServerVars::DOCUMENT_ROOT) . DIRECTORY_SEPARATOR . $this->targetString
                );
                $serverContext->setServerVar(ServerVars::SCRIPT_NAME, $this->targetString);
                $serverContext->setServerVar('REDIRECT_QUERY_STRING', substr(strstr($this->targetString, '?'), 1));
                // TODO the REQUEST_URI is the wrong thing to change, but we currently need this set
                // TODO we have to set the QUERY_STRING for the same reason
                // Requested uri always has to begin with a slash
                $this->targetString = '/' . ltrim($this->targetString, '/');
                $serverContext->setServerVar(ServerVars::REQUEST_URI, $this->targetString);
                $serverContext->setServerVar(ServerVars::QUERY_STRING, substr(strstr($this->targetString, '?'), 1));
            }
        }
        // If we got the "L"-flag we have to end here, so return false
        if (strpos($this->flagString, 'L') !== false) {

            return false;
        }

        // Still here? That sounds good
        return true;
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
    public function getTargetString()
    {
        return $this->targetString;
    }
}
