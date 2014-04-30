<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Appserver
 * @package    TechDivision_WebServer
 * @subpackage Configuration
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\WebServer\Configuration\Extension;

/**
 * TechDivision\WebServer\Configuration\Extension\StringInjector
 *
 * <TODO CLASS DESCRIPTION>
 *
 * @category   Appserver
 * @package    TechDivision_WebServer
 * @subpackage Configuration
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class StringInjector extends AbstractInjector
{
    /**
     * @var  $data <TODO FIELD COMMENT>
     */
    protected $data;

    /**
     * Delimeter for the extracted string
     *
     * @const string STRING_DELIMETER
     */
    const STRING_DELIMETER = ' ';

    /**
     * Will init the injector's datasource
     *
     * @return void
     */
    public function init()
    {
        // Init data as an empty array
        $this->data = array();

        // Grab our DB resource
        $dbConnection = $this->getDbResource();

        // Build up the query
        $query = 'SELECT * FROM "virtualHost"';

        // Get the results and fill them into our data
        foreach ($dbConnection->query($query) as $row) {

            $this->data[] = $row[0];
        }
    }

    /**
     * We will return a string containing all data entries delimetered by the configured delimeter
     *
     * @return mixed
     */
    public function extract()
    {
        // Iterate over all entries and concatenate them
        $result = '';
        foreach ($this->data as $dataEntry) {

            $result .= $dataEntry . self::STRING_DELIMETER;
        }

        // Cut the last delimeter
        return substr($result, 0, strlen($result) - strlen(self::STRING_DELIMETER));
    }
}
