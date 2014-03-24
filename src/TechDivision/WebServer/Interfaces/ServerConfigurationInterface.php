<?php
/**
 * \TechDivision\WebServer\Interfaces\ServerConfigurationInterface
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
     * Return's software
     *
     * @return string
     */
    public function getSoftware();

    /**
     * Return's admin
     *
     * @return string
     */
    public function getAdmin();

    /**
     * Return's keep-alive max connection
     *
     * @return int
     */
    public function getKeepAliveMax();

    /**
     * Return's keep-alive timeout
     *
     * @return int
     */
    public function getKeepAliveTimeout();

    /**
     * Return's template path for errors page
     *
     * @return string
     */
    public function getErrorsPageTemplatePath();

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
     * @return array
     */
    public function getModules();

    /**
     * Return's connection handlers
     *
     * @return array
     */
    public function getConnectionHandlers();

    /**
     * Return's the virtual hosts
     *
     * @return array
     */
    public function getVirtualHosts();

    /**
     * Return's the authentications
     *
     * @return array
     */
    public function getAuthentications();

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

    /**
     * Returns the rewrite configuration.
     *
     * @return array
     */
    public function getRewrites();

    /**
     * Will prepend a given array of rewrite arrays to the global rewrite pool.
     * Rewrites arrays have to be the form of array('condition' => ..., 'target' => ..., 'flag' => ...)
     *
     * @param array $rewriteArrays The array of rewrite arrays(!) to prepend
     *
     * @return boolean
     */
    public function prependRewriteArrays(array $rewriteArrays);
}
