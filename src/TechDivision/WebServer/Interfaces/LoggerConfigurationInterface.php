<?php
/**
 * \TechDivision\WebServer\Interfaces\LoggerConfigurationInterface
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
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */

namespace TechDivision\WebServer\Interfaces;

/**
 * Interface LoggerConfigurationInterface
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
interface LoggerConfigurationInterface
{
    /**
     * Return's name
     *
     * @return string
     */
    public function getName();

    /**
     * Return's type
     *
     * @return string
     */
    public function getType();

    /**
     * Return's channel
     *
     * @return string
     */
    public function getChannel();

    /**
     * Return's defined handlers for logger
     *
     * @return array
     */
    public function getHandlers();

    /**
     * Return's defined processors for logger
     *
     * @return array
     */
    public function getProcessors();
}
