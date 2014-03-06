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
 * @category   Php-by-contract
 * @package    TechDivision_WebServer
 * @subpackage ConfigParser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\WebServer\ConfigParser;

use TechDivision\WebServer\ConfigParser\Directives;

/**
 * TechDivision\WebServer\ConfigParser\HtaccessParser
 *
 * <TODO CLASS DESCRIPTION>
 *
 * @category   Php-by-contract
 * @package    TechDivision_WebServer
 * @subpackage ConfigParser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */
class HtaccessParser extends AbstractParser
{
    /**
     * The type of configuration we can parse
     *
     * @const string CONFIG_TYPE
     */
    const CONFIG_TYPE = '.htaccess';

    /**
     * Will return the type of the configuration as the parser might encounter different configuration types
     *
     * @return string
     */
    public function getConfigType()
    {
        return self::CONFIG_TYPE;
    }

    /**
     * Will return a complete configuration parsed from the provided file
     *
     * @param string $filePath The path to the configuration file
     *
     * @return \TechDivision\WebServer\ConfigParser\Config
     */
    public function getConfigForFile($filePath)
    {
        $fileInfo = new \SplFileInfo($filePath);

        // We will check each directory from the requested URI's path upwards and if we find a local config file we
        // will take it ;)
        $configPath = '';
        $depth = count(explode(DIRECTORY_SEPARATOR, $filePath));
        for ($i = 0; $i <= $depth; $i++) {

            $fileInfo = $fileInfo->getPathInfo();
            if ($fileInfo->isDir()) {

                $directoryContent = array_flip(scandir($fileInfo->getPath()));
                if (isset($directoryContent[self::CONFIG_TYPE])) {

                    $configPath = $fileInfo->getPath() . DIRECTORY_SEPARATOR . self::CONFIG_TYPE;
                    break;
                }
            }
        }

        // We have to read the file line per line, filter the directives and group them into modules
        $lines = file($configPath, FILE_IGNORE_NEW_LINES);

        // Iterate over all lines and parse the directives from then
        $directives = array();
        foreach ($lines as $line) {

            $directives[] = $this->getDirectiveFromLine($line);
        }

        return new Config($configPath, $directives);
    }

    /**
     * <TODO FUNCTION DESCRIPTION>
     *
     * @param $line
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getDirectivesFromLine($line)
    {
        // Split the line into pieces and check which directive we got
        $lineParts = explode(' ', $line);

        // What would be the directive class name?
        $className = 'Directives\\' . $lineParts[0];

        if (!class_exists($className)) {

            throw new \Exception('Unknown directive in line ' . $line);
        }

        // Create the directive and let it be filled
        $directive = new $className();
        unset($lineParts[0]);
        $directive->fillFromArray($lineParts);

        return $directive;
    }
}
