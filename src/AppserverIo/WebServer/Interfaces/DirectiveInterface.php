<?php

/**
 * \AppserverIo\WebServer\Interfaces\DirectiveInterface
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/appserver-io/webserver
 * @link      http://www.appserver.io
 */

namespace AppserverIo\WebServer\Interfaces;

/**
 * This interface acts as a very basic interface for directives of config files
 *
 * @author Johann Zelger <jz@appserver.io>
 * @copyright 2015 TechDivision GmbH <info@appserver.io>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link https://github.com/appserver-io/webserver
 * @link http://www.appserver.io
 */
interface DirectiveInterface
{

    /**
     * Will fill an empty directive object with vital information delivered via an array.
     * This is mostly useful as an interface for different parsers
     *
     * @param array $parts The array to extract information from
     *
     * @return null
     * @throws \InvalidArgumentException
     */
    public function fillFromArray(array $parts);
}
