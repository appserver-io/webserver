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
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage ConfigParser
 * @author     Bernhard Wick <b.wick@techdivision.com>
 * @copyright  2014 TechDivision GmbH - <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php
 *             Open Software License (OSL 3.0)
 * @link       http://www.techdivision.com/
 */

namespace TechDivision\WebServer\ConfigParser;

/**
 * TechDivision\WebServer\ConfigParser\HtaccessParser
 *
 * This class provides a very basic htaccess parser which is able to extract several htaccess specific
 * directives.
 *
 * @category   Webserver
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
     * A prefix which indicates a comment in a config file
     *
     * @const string COMMENT_PREFIX
     */
    const COMMENT_PREFIX = '#';

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
     * @param string $documentRoot The servers document root as a fallback
     * @param string $requestedUri The requested uri
     *
     * @return \TechDivision\WebServer\ConfigParser\Config
     */
    public function getConfigForFile($documentRoot, $requestedUri)
    {
        // Get the path to the requested uri
        $fileInfo = new \SplFileInfo($documentRoot . DIRECTORY_SEPARATOR . $requestedUri);

        // We will check each directory from the requested URI's path upwards and if we find a local config file we
        // will take it ;)
        $configPath = '';
        $depth = count(explode(DIRECTORY_SEPARATOR, $fileInfo));
        for ($i = 0; $i <= $depth; $i++) {

            if ($fileInfo->isDir()) {

                $directoryContent = array_flip(scandir($fileInfo));
                if (isset($directoryContent[self::CONFIG_TYPE])) {

                    $configPath = $fileInfo . DIRECTORY_SEPARATOR . self::CONFIG_TYPE;
                    break;
                }
            }

            // Set move one directory up the in the file hierarchy
            $fileInfo = $fileInfo->getPathInfo();
        }

        // We have to read the file line per line, filter the directives and group them into modules
        $lines = file($configPath, FILE_IGNORE_NEW_LINES);

        // Iterate over all lines and parse the directives from then
        $directives = array();
        foreach ($lines as $line) {

            // Check if we got a comment
            if (strpos($line, self::COMMENT_PREFIX) === 0) {

                continue;
            }

            // Get the directive for this line
            $directives[] = $this->getDirectiveFromLine($line);
        }

        return new Config($configPath, $directives);
    }

    /**
     * This method will extract known directives from a single htaccess line.
     *
     * @param string $line A line from a htaccess file
     *
     * @return \TechDivision\WebServer\Interfaces\DirectiveInterface
     * @throws \Exception
     */
    protected function getDirectiveFromLine($line)
    {
        // Split the line into pieces and check which directive we got
        $lineParts = explode(' ', $line);

        // What would be the directive class name?
        $className = __NAMESPACE__ . '\Directives\\' . $lineParts[0];

        if (!class_exists($className)) {

            throw new \Exception('Unknown directive in line ' . $line);
        }

        // Create the directive and let it be filled
        $directive = new $className();
        $directive->fillFromArray($lineParts);

        return $directive;
    }
}
