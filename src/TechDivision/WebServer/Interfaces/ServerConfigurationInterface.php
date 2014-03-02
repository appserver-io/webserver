<?php
/**
 * \TechDivision\WebServer\Interfaces\ServerConfigurationInterface
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
 * Interface ServerConfigurationInterface
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Interfaces
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebServer
 */
interface ServerConfigurationInterface
{
    /**
     * Return's type
     *
     * @return string
     */
    public function getType();

    /**
     * Return's transport
     *
     * @return string
     */
    public function getTransport();

    /**
     * Return's address
     *
     * @return string
     */
    public function getAddress();

    /**
     * Return's port
     *
     * @return int
     */
    public function getPort();

    /**
     * Return's workerNumber
     *
     * @return int
     */
    public function getWorkerNumber();

    /**
     * Return's signature
     *
     * @return string
     */
    public function getSignature();

    /**
     * Return's server context type
     *
     * @return string
     */
    public function getServerContextType();

    /**
     * Return's socket type
     *
     * @return string
     */
    public function getSocketType();

    /**
     * Return's worker type
     *
     * @return string
     */
    public function getWorkerType();

    /**
     * Return's document root
     *
     * @return string
     */
    public function getDocumentRoot();

    /**
     * Return's modules
     *
     * @return string
     */
    public function getModules();

    /**
     * Return's connection handlers
     *
     * @return array
     */
    public function getConnectionHandlers();

    /**
     * Return's handlers
     *
     * @return array
     */
    public function getHandlers();

    /**
     * Return's certPath
     *
     * @return string
     */
    public function getCertPath();

    /**
     * Return's passphrase
     *
     * @return string
     */
    public function getPassphrase();
}
