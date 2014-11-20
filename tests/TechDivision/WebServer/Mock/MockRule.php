<?php

/**
 * \AppserverIo\WebServer\Mock\MockRule
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
 * @subpackage Mock
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */

namespace AppserverIo\WebServer\Mock;

use AppserverIo\RewriteModule\Entities\Rule;

/**
 * Class MockRule
 *
 * Mocks the Rule class to expose additional and hidden functionality
 *
 * @category   Server
 * @package    WebServer
 * @subpackage Mock
 * @author     Bernhard Wick <bw@appserver.io>
 * @copyright  2014 TechDivision GmbH <info@appserver.io>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/appserver-io/webserver
 */
class MockRule extends Rule
{
    /**
     * Used to open up the parent's sortFlags() method for testing
     *
     * @param string $flagString The unsorted string of flags
     *
     * @return array
     */
    public function sortFlags($flagString)
    {
        return parent::sortFlags($flagString);
    }

    /**
     * Getter function for the protected $type member
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Getter function for the protected $sortedFlags member
     *
     * @return array
     */
    public function getSortedFlags()
    {
        return $this->sortedFlags;
    }
}
