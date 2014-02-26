<?php
/**
 * \TechDivision\WebServer\Dictionary\MimeTypes
 *
 * PHP version 5
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Dictionary
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace TechDivision\WebServer\Dictionary;

/**
 * Class MimeTypes
 *
 * @category   Webserver
 * @package    TechDivision_WebServer
 * @subpackage Dictionary
 * @author     Johann Zelger <jz@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class MimeTypes {

    /**
     * Defines mimetype default
     *
     * @var string
     */
    const MIMETYPE_DEFAULT = "application/octet-stream";

    /**
     * Get the MIME Type for the specified filename.
     *
     * @param string $filename The filename to get mimeType for
     *
     * @return string
     */
    public static function getMimeTypeByFilename($filename) {
        // check if there is an filename extension
        $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
        if ($fileExtension) {
            // check if there is a mimeType for this extension
            if ($mimeType = self::getMimeTypeByExtension($fileExtension)) {
                return $mimeType;
            }
        }
        // return mimeType default
        return self::MIMETYPE_DEFAULT;
    }

    /**
     * Return's the mimeType by given extension
     *
     * @param string $extension The extension to find the mime type for
     *
     * @return string
     */
    public static function getMimeTypeByExtension($extension) {
        // normalize extension by doing lower str
        $extension = strtolower($extension);
        if (isset(self::$types[$extension])) {
            return self::$types[$extension];
        }
        return self::MIMETYPE_DEFAULT;
    }

    public static $types = array(
        "html" => "text/html",
        "htm" => "text/html",
        "shtml" => "text/html",
        "css" => "text/css",
        "xml" => "text/xml",
        "rss" => "text/xml",
        "gif" => "image/gif",
        "jpeg" => "image/jpeg",
        "jpg" => "image/jpeg",
        "js" => "application/x-javascript",
        "atom" => "application/atom+xml",
        "mml" => "text/mathml",
        "txt" => "text/plain",
        "jad" => "text/vnd.sun.j2me.app-descriptor",
        "wml" => "text/vnd.wap.wml",
        "htc" => "text/x-component",
        "png" => "image/png",
        "tif" => "image/tiff",
        "tiff" => "image/tiff",
        "wbmp" => "image/vnd.wap.wbmp",
        "ico" => "image/x-icon",
        "jng" => "image/x-jng",
        "bmp" => "image/x-ms-bmp",
        "svg" => "image/svg+xml",
        "svgz" => "image/svg+xml",
        "jar" => "application/java-archive",
        "war" => "application/java-archive",
        "ear" => "application/java-archive",
        "json" => "application/json",
        "hqx" => "application/mac-binhex40",
        "doc" => "application/msword",
        "pdf" => "application/pdf",
        "ps" => "application/postscript",
        "eps" => "application/postscript",
        "ai" => "application/postscript",
        "rtf" => "application/rtf",
        "xls" => "application/vnd.ms-excel",
        "ppt" => "application/vnd.ms-powerpoint",
        "wmlc" => "application/vnd.wap.wmlc",
        "kml" => "application/vnd.google-earth.kml+xml",
        "kmz" => "application/vnd.google-earth.kmz",
        "7z" => "application/x-7z-compressed",
        "cco" => "application/x-cocoa",
        "jardiff" => "application/x-java-archive-diff",
        "jnlp" => "application/x-java-jnlp-file",
        "run" => "application/x-makeself",
        "pl" => "application/x-perl",
        "pm" => "application/x-perl",
        "prc" => "application/x-pilot",
        "pdb" => "application/x-pilot",
        "rar" => "application/x-rar-compressed",
        "rpm" => "application/x-redhat-package-manager",
        "sea" => "application/x-sea",
        "swf" => "application/x-shockwave-flash",
        "sit" => "application/x-stuffit",
        "tcl" => "application/x-tcl",
        "tk" => "application/x-tcl",
        "der" => "application/x-x509-ca-cert",
        "pem" => "application/x-x509-ca-cert",
        "crt" => "application/x-x509-ca-cert",
        "xpi" => "application/x-xpinstall",
        "xhtml" => "application/xhtml+xml",
        "zip" => "application/zip",
        "bin" => "application/octet-stream",
        "exe" => "application/octet-stream",
        "dll" => "application/octet-stream",
        "deb" => "application/octet-stream",
        "dmg" => "application/octet-stream",
        "eot" => "application/octet-stream",
        "iso" => "application/octet-stream",
        "img" => "application/octet-stream",
        "msi" => "application/octet-stream",
        "msp" => "application/octet-stream",
        "msm" => "application/octet-stream",
        "ogx" => "application/ogg",
        "mid" => "audio/midi",
        "midi" => "audio/midi",
        "kar" => "audio/midi",
        "mpga" => "audio/mpeg",
        "mpega" => "audio/mpeg",
        "mp2" => "audio/mpeg",
        "mp3" => "audio/mpeg",
        "m4a" => "audio/mpeg",
        "oga" => "audio/ogg",
        "ogg" => "audio/ogg",
        "spx" => "audio/ogg",
        "ra" => "audio/x-realaudio",
        "weba" => "audio/webm",
        "3gpp 3gp" => "video/3gpp",
        "mp4" => "video/mp4",
        "mpeg" => "video/mpeg",
        "mpg" => "video/mpeg",
        "mpe" => "video/mpeg",
        "ogv" => "video/ogg",
        "mov" => "video/quicktime",
        "webm" => "video/webm",
        "flv" => "video/x-flv",
        "mng" => "video/x-mng",
        "asx" => "video/x-ms-asf",
        "asf" => "video/x-ms-asf",
        "wmv" => "video/x-ms-wmv",
        "avi" => "video/x-msvideo"
    );

}