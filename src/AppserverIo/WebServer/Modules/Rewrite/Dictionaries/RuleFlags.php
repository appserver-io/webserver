<?php

/**
 * AppserverIo\WebServer\Modules\Rewrite\Dictionaries\RuleFlags
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

namespace AppserverIo\WebServer\Modules\Rewrite\Dictionaries;

/**
 * Class RuleFlags
 *
 * This file is a dictionary for rule flags.
 * Defines constant for flags we might use within the rule's flag field
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Modules
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
class RuleFlags
{
    /**
     * Make a redirect instead of a mere rewrite
     *
     * @var string
     */
    const REDIRECT = 'R';

    /**
     * Take the target from a specified map
     *
     * @var string
     */
    const MAP = 'M';

    /**
     * Do not process following rewrite rules if this one matches
     *
     * @var string
     */
    const LAST = 'L';
}
