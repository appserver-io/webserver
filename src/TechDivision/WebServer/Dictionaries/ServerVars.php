<?php
/**
 * \TechDivision\WebServer\Dictionaries\ServerVars
 *
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
 * @subpackage Dictionaries
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Dictionaries;

/**
 * Class ServerVars
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Dictionaries
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class ServerVars
{
    /**
     * Defines HTTP header vars
     *
     * @var string
     */
    const HTTP_USER_AGENT = 'HTTP_USER_AGENT';
    const HTTP_REFERER = 'HTTP_REFERER';
    const HTTP_COOKIE = 'HTTP_COOKIE';
    const HTTP_FORWARDED = 'HTTP_FORWARDED';
    const HTTP_HOST = 'HTTP_HOST';
    const HTTP_PROXY_CONNECTION = 'HTTP_PROXY_CONNECTION';
    const HTTP_X_REQUESTED_WITH = 'HTTP_X_REQUESTED_WITH';
    const HTTP_ACCEPT = 'HTTP_ACCEPT';
    const HTTP_ACCEPT_CHARSET = 'HTTP_ACCEPT_CHARSET';
    const HTTP_ACCEPT_ENCODING = 'HTTP_ACCEPT_ENCODING';
    const HTTP_ACCEPT_LANGUAGE = 'HTTP_ACCEPT_LANGUAGE';
    const HTTP_CONNECTION = 'HTTP_CONNECTION';

    /**
     * Defines server internal vars
     *
     * @var string
     */
    const DOCUMENT_ROOT = 'DOCUMENT_ROOT';
    const SERVER_ADMIN = 'SERVER_ADMIN';
    const SERVER_NAME = 'SERVER_NAME';
    const SERVER_ADDR = 'SERVER_ADDR';
    const SERVER_PORT = 'SERVER_PORT';
    const SERVER_PROTOCOL = 'SERVER_PROTOCOL';
    const SERVER_SOFTWARE = 'SERVER_SOFTWARE';
    const SERVER_SIGNATURE = 'SERVER_SIGNATURE';
    const SERVER_HANDLER = 'SERVER_HANDLER';
    const SERVER_ERRORS_PAGE_TEMPLATE_PATH = 'SERVER_ERRORS_PAGE_TEMPLATE_PATH';
    const PATH = 'PATH';

    /**
     * Defines connection & request vars
     *
     * @var string
     */
    const GATEWAY_INTERFACE = 'GATEWAY_INTERFACE';
    const AUTH_TYPE = 'AUTH_TYPE';
    const REMOTE_ADDR = 'REMOTE_ADDR';
    const REMOTE_HOST = 'REMOTE_HOST';
    const REMOTE_PORT = 'REMOTE_PORT';
    const REMOTE_USER = 'REMOTE_USER';
    const REMOTE_IDENT = 'REMOTE_IDENT';
    const REDIRECT_REMOTE_USER = 'REDIRECT_REMOTE_USER';
    const REDIRECT_STATUS = 'REDIRECT_STATUS';
    const REQUEST_METHOD = 'REQUEST_METHOD';
    const SCRIPT_FILENAME = 'SCRIPT_FILENAME';
    const SCRIPT_NAME = 'SCRIPT_NAME';
    const PATH_TRANSLATED = 'PATH_TRANSLATED';
    const PATH_INFO = 'PATH_INFO';
    const ORIG_PATH_INFO = 'ORIG_PATH_INFO';
    const QUERY_STRING = 'QUERY_STRING';

    /**
     * Defines date and time vars
     *
     * @var string
     */
    const REQUEST_TIME = 'REQUEST_TIME';
    const REQUEST_TIME_FLOAT = 'REQUEST_TIME_FLOAT';
    const TIME_YEAR = 'TIME_YEAR';
    const TIME_MON = 'TIME_MON';
    const TIME_DAY = 'TIME_DAY';
    const TIME_HOUR = 'TIME_HOUR';
    const TIME_MIN = 'TIME_MIN';
    const TIME_SEC = 'TIME_SEC';
    const TIME_WDAY = 'TIME_WDAY';
    const TIME = 'TIME';

    /**
     * Defines special vars
     *
     * @var string
     */
    const API_VERSION = 'API_VERSION';
    const THE_REQUEST = 'THE_REQUEST';
    const REQUEST_URI = 'REQUEST_URI';
    // This special constant is used to allow inter-module communication without changing the original REQUEST_URI
    const X_REQUEST_URI = 'X_REQUEST_URI';
    const REQUEST_FILENAME = 'REQUEST_FILENAME';
    const IS_SUBREQ = 'IS_SUBREQ';
    const HTTPS = 'HTTPS';

    /**
     * Defines special value consts
     *
     * @var string
     */
    const VALUE_HTTPS_ON = 'on';
    const VALUE_HTTPS_OFF = 'off';

    /**
     * Defines logger types
     *
     * @var string
     */
    const LOGGER_ACCESS = 'LOGGER_ACCESS';
    const LOGGER_SYSTEM = 'LOGGER_SYSTEM';
}
