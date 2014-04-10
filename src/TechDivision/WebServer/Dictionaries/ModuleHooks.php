<?php
/**
 * \TechDivision\WebServer\Dictionaries\ModuleHooks
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
 * Class ModuleHooks
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Dictionaries
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
class ModuleHooks
{
    /**
     * The request pre hook should be used to do something before the request will be parsed.
     * So if there is a keep-alive loop going on this will be triggered every request loop.
     *
     * @var string
     */
    const REQUEST_PRE = 1;

    /**
     * The request post hook should be used to do something after the request has been parsed.
     * Most modules such as CoreModule will use this hook to do their job.
     *
     * @var string
     */
    const REQUEST_POST = 2;

    /**
     * The response pre hook will be triggered at the point before the response will be prepared
     * for sending it to the to the connection endpoint.
     *
     * @var string
     */
    const RESPONSE_PRE = 3;

    /**
     * The response post hook is the last hook triggered within a keep-alive loop and will execute
     * the modules logic when the response is well prepared and ready to dispatch
     *
     * @var string
     */
    const RESPONSE_POST = 4;
}
