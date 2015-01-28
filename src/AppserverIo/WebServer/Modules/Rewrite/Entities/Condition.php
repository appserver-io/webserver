<?php

/**
 * AppserverIo\WebServer\Modules\Rewrite\Entities\Condition
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
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */

namespace AppserverIo\WebServer\Modules\Rewrite\Entities;

use AppserverIo\WebServer\Modules\Rewrite\Dictionaries\ConditionActions;
use AppserverIo\Server\Dictionaries\ServerVars;

/**
 * Class Condition
 *
 * This class provides an object based representation of a rewrite rules condition including logic for checking itself.
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class Condition
{

    /**
     * The allowed values for the $types member
     *
     * @var array<string> $allowedTypes
     */
    protected $allowedTypes = array();

    /**
     * All possible modifiers aka flags
     *
     * @var array<string> $flagMapping
     */
    protected $allowedModifiers = array();

    /**
     * Possible additions to the known PCRE regex.
     * These additions get used by htaccess notation only.
     *
     * @var array<string> $htaccessAdditions
     */
    protected $htaccessAdditions = array();

    /**
     * The type of this condition
     *
     * @var string $type
     */
    protected $type;

    /**
     * The value to check with the given action
     *
     * @var string $operand
     */
    protected $operand;

    /**
     * In some cases, e.g.
     * string comparison or regex, we need another operand to work with
     *
     * @var string $additionalOperand
     */
    protected $additionalOperand;

    /**
     * How the operand has to be checked, this will hold the needed action as a string and cannot be
     * processed automatically.
     *
     * @var string $action
     */
    protected $action;

    /**
     * Modifier which should be used to integrate things like apache flags and others
     *
     * @var string $modifier
     */
    protected $modifier;

    /**
     * At least in the apache universe we can negate the logical meaning with a "!"
     *
     * @var boolean $isNegated
     */
    protected $isNegated;

    /**
     * Default constructor
     *
     * @param string $operand  The value to check with the given action
     * @param string $action   How the operand has to be checked, this will hold the needed action
     * @param string $modifier Modifier which should be used to integrate things like apache flags and others
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($operand, $action, $modifier = '')
    {
        // Fill the default values for our members here
        $this->allowedTypes = array(
            'regex',
            'check'
        );
        $this->htaccessAdditions = array(
            ConditionActions::STR_LESS,
            ConditionActions::STR_GREATER,
            ConditionActions::STR_EQUAL,
            ConditionActions::IS_DIR,
            ConditionActions::IS_FILE,
            ConditionActions::IS_USED_FILE,
            ConditionActions::IS_LINK,
            ConditionActions::IS_EXECUTABLE
        );
        $this->allowedModifiers = array(
            '[NC]',
            '[nocase]'
        );

        // We do not negate by default, nor do we combine with the following condition via "or"
        $this->isNegated = false;

        // Check if the passed modifier is valid (or empty)
        if (! isset(array_flip($this->allowedModifiers)[$modifier]) && ! empty($modifier)) {
            throw new \InvalidArgumentException($modifier . ' is not an allowed condition modifier.');
        }

        // Fill the more important properties
        $this->operand = $operand;
        $this->action = $action;
        $this->modifier = $modifier;
        $this->additionalOperand = '';

        // Check if we have a negation
        $this->preparePossibleNegation();

        // check what type we have. Per default it's regex
        $this->prepareOperandAdditions();

        // If we got a regex we have to re-organize a few things
        if ($this->type !== 'check') {
            // we have to set the type correctly, collect the regex as additional operand and set the regex flag as
            // action to allow proper switching of functions later
            $this->type = 'regex';
            $this->additionalOperand = $this->action;
            $this->action = ConditionActions::REGEX;
        }
    }

    /**
     * Checks if we are we able to find any of the additions htaccess syntax offers.
     *
     * @return null
     */
    protected function prepareOperandAdditions()
    {
        foreach ($this->htaccessAdditions as $addition) {
            // The string has to start with an addition (any negating ! was cut of before)
            if (strpos($this->action, $addition) === 0) {
                // If we have a string comparing action we have to cut it to know what to compare to, otherwise we
                // need the document root as an additional operand
                $tmp = substr($this->action, 0, 1);
                if ($tmp === '<' || $tmp === '>' || $tmp === '=') {
                    // We have to extract the needed parts of our operation and refill it into
                    // our additional operand string
                    $this->additionalOperand = substr($this->action, 1);
                    $this->action = substr($this->action, 0, 1);
                }

                // If we reach this point we are of the check type
                $this->type = 'check';
                break;
            }
        }
    }

    /**
     * Will check if we have a possible negation and act accordingly
     *
     * @return null
     */
    protected function preparePossibleNegation()
    {
        // Check if we have a negation
        if (strpos($this->action, '!') === 0) {
            // Tell them we have to negate the check
            $this->isNegated = true;
            // Remove the "!" as it might kill the regex otherwise
            $this->action = ltrim($this->action, '!');
        }
    }

    /**
     * Getter for the $type member
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Getter for the $operand member
     *
     * @return string
     */
    public function getOperand()
    {
        return $this->operand;
    }

    /**
     * Getter for the $modifier member
     *
     * @return string
     */
    public function getModifier()
    {
        return $this->modifier;
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

        // Substitute the backreferences in our operand and additionalOperand
        $this->operand = str_replace($backreferenceHolders, $backreferenceValues, $this->operand);

        // prepare our operand to be usable for filesystem checks
        $this->prepareFilesystemOperand();
        $this->additionalOperand = str_replace($backreferenceHolders, $backreferenceValues, $this->additionalOperand);
    }

    /**
     * Will return true if the condition is true, false if not
     *
     * @return boolean
     * @throws \InvalidArgumentException
     */
    public function matches()
    {
        // Switching between different actions we have to take.
        // Using an if cascade as it seems to be faster than switch...case
        $result = false;
        if ($this->action === ConditionActions::REGEX) {
            // Get the result for a regex

            $result = preg_match('`' . $this->additionalOperand . '`', $this->operand) === 1;
        } elseif ($this->action === ConditionActions::IS_DIR) {
            // Is it an existing directory?

            $result = is_dir($this->additionalOperand . $this->operand);
        } elseif ($this->action === ConditionActions::IS_EXECUTABLE) {
            // Is the file an executable?

            $result = is_executable($this->additionalOperand . $this->operand);
        } elseif ($this->action === ConditionActions::IS_FILE) {
            // Is it a regular file?

            $result = is_file($this->additionalOperand . $this->operand);
        } elseif ($this->action === ConditionActions::IS_LINK) {
            // Is it a symlink?

            $result = is_link($this->additionalOperand . $this->operand);
        } elseif ($this->action === ConditionActions::IS_USED_FILE) {
            // Is it a real file which has a size greater 0?

            $result = (is_file($this->additionalOperand . $this->operand) && (int) filesize($this->additionalOperand . $this->operand) > 0);
        } elseif ($this->action === ConditionActions::STR_EQUAL) {
            // Or the compared strings equal

            $result = strcmp($this->operand, $this->additionalOperand) == 0;
        } elseif ($this->action === ConditionActions::STR_GREATER) {
            // Is the operand bigger?

            $result = strcmp($this->operand, $this->additionalOperand) > 0;
        } elseif ($this->action === ConditionActions::STR_LESS) {
            // Is the operand smaller?
            $result = strcmp($this->operand, $this->additionalOperand) < 0;
        }

        // If the check got negated we will just negate what we got from our preceding checks
        if ($this->isNegated) {
            $result = ! $result;
        }

        return $result;
    }

    /**
     * Will prepare a filesystem enabled operand by cutting of any traces of a query string
     *
     * @return null
     */
    protected function prepareFilesystemOperand()
    {
        if ($this->action === ConditionActions::IS_DIR || $this->action === ConditionActions::IS_EXECUTABLE || $this->action === ConditionActions::IS_FILE || $this->action === ConditionActions::IS_LINK || $this->action === ConditionActions::IS_USED_FILE) {
            if (strpos($this->operand, '?') !== false) {
                $this->operand = strstr($this->operand, '?', true);
            }

            if (! is_readable($this->additionalOperand . $this->operand)) {
                // Set the placeholder for the document root, it will be resolved anyway
                // If we got ourselves a complete path, we do not need the document root
                $this->additionalOperand = '$' . ServerVars::DOCUMENT_ROOT;
            }
        }
    }

    /**
     * Will collect all backreferences based on regex typed conditions
     *
     * @return array
     */
    public function getBackreferences()
    {
        $backreferences = array();
        $matches = array();
        if ($this->type === 'regex') {
            preg_match('`' . $this->additionalOperand . '`', $this->operand, $matches);

            // Unset the first find of our backreferences, so we can use it automatically
            unset($matches[0]);
        }

        // Iterate over all our found matches and give them a fine name
        foreach ($matches as $key => $match) {
            $backreferences['$' . (string) $key] = $match;
        }

        return $backreferences;
    }
}
