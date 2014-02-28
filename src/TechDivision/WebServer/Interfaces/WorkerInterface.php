<?php
/**
 * \TechDivision\WebServer\Interfaces\WorkerInterface
 *
 * PHP version 5
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace TechDivision\WebServer\Interfaces;

/**
 * Interface WorkerInterface
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
interface WorkerInterface
{
    /**
     * Return's the server context instance
     *
     * @return \TechDivision\WebServer\Interfaces\ServerContextInterface The server's context
     */
    public function getServerContext();

    /**
     * Start's the worker
     *
     * @return void
     */
    public function run();

    /**
     * Implements the workers actual logic
     *
     * @return void
     */
    public function work();

}
