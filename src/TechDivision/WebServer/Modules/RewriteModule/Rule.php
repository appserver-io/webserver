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
        // Separate the keys from the values so we can use them in str_replace
        $backreferenceHolders = array_keys($backreferences);
        $backreferenceValues = array_values($backreferences);

        // Substitute the backreferences in our operation
        $this->targetString = str_replace($backreferenceHolders, $backreferenceValues, $this->targetString);
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
            }

            // The single conditions have to match as they are and-combined
            if (!$sortedCondition->matches()) {

                return false;
            }
        }

        // We are still here, this sounds good
        return true;
    }

    /**
     * Will collect all backreferences based on regex typed conditions
     *
     * @param integer $offset       The offset to count from, used so no integer based directive will be overwritten
     * @param string  $requestedUri The requested URI as implicit part of the rule
     *
     * @return array
     */
    public function getBackreferences($offset, $requestedUri)
    {
        $backreferences = array();
        $matches = array();
        if ($this->type === 'relative') {

            preg_match('`' . $this->pattern . '`', $requestedUri, $matches);

            // Unset the first find of our backreferences, so we can use it automatically
            unset($matches[0]);
        }

        // Iterate over all our found matches and give them a fine name
        foreach ($matches as $key => $match) {

            $backreferences['$' . (string)($offset + $key)] = $match;
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
