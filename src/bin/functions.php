<?php
/**
 * functions.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category  Webserver
 * @package   TechDivision_WebServer
 * @author    Johann Zelger <jz@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/TechDivision_Http
 */

/**
 * Override this function to use global server vars
 *
 * @param string $env The env var to get
 *
 * @return string
 * @todo: refactor this to an static function lib
 */
function getenv($env)
{
    if (isset($_SERVER[$env])) {
        return $_SERVER[$env];
    }
}

/**
 * Override this function to return false in any case
 *
 * @return bool
 */
function headers_sent()
{
    return false;
}
