<?php

/**
 * \AppserverIo\WebServer\Mock\MockCondition
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

namespace AppserverIo\WebServer\Mock;

use AppserverIo\WebServer\Modules\Rewrite\Entities\Condition;

/**
 * Class MockCondition
 *
 * Mocks the Condition class to expose additional functionality
 *
 * @author    Bernhard Wick <bw@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io/
 */
class MockCondition extends Condition
{
    /**
     * Getter for the $additionalOperand member
     *
     * @return string
     */
    public function getAdditionalOperand()
    {
        return $this->additionalOperand;
    }

    /**
     * Getter for the $action member
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Getter for the $isNegated member
     *
     * @return boolean
     */
    public function getIsNegated()
    {
        return $this->isNegated;
    }
}
