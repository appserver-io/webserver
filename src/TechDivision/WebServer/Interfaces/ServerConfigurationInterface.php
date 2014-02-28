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
 */
interface ServerConfigurationInterface
{
    public function getType();

    public function getTransport();

    public function getAddress();

    public function getPort();

    public function getWorkerNumber();

    public function getSignature();

    public function getServerContextType();

    public function getSocketType();

    public function getWorkerType();

    public function getDocumentRoot();

    public function getModules();

    public function getConnectionHandlers();

    public function getHandlers();

    public function getCertPath();

    public function getPassphrase();

}
