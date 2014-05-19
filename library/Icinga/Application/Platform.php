<?php
// @codeCoverageIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Exception;

/**
 * Utility class to provide information about the current system icingaweb is running on
 */
class Platform
{
    /**
     * The hostname
     *
     * @var string
     */
    protected static $hostname;

    /**
     * The domain
     *
     * @var string
     */
    protected static $domain;

    /**
     * The fully qualified domain name (FQDN)
     *
     * @var string
     */
    protected static $fqdn;

    /**
     * Return the hostname
     *
     * @return  string
     */
    public static function getHostname()
    {
        if (self::$hostname === null) {
            self::discoverHostname();
        }

        return self::$hostname;
    }

    /**
     * Return the domain
     *
     * @return  string
     */
    public static function getDomain()
    {
        if (self::$domain === null) {
            self::discoverHostname();
        }

        return self::$domain;
    }

    /**
     * Return the fully qualified domain name (FQDN)
     *
     * @return  string
     */
    public static function getFqdn()
    {
        if (self::$fqdn === null) {
            self::discoverHostname();
        }

        return self::$fqdn;
    }

    /**
     * Discover the hostname, domain and fqdn
     */
    protected static function discoverHostname()
    {
        self::$hostname = gethostname();
        self::$fqdn = gethostbyaddr(gethostbyname(self::$hostname));

        if (substr(self::$fqdn, 0, strlen(self::$hostname)) === self::$hostname) {
            self::$domain = substr(self::$fqdn, strlen(self::$hostname) + 1);
        } else {
            self::$domain = array_shift(preg_split('~\.~', self::$hostname, 2));
        }
    }

    /**
     * Return whether the operating system is Windows
     *
     * @return  bool
     */
    public static function isWindows()
    {
        return strtoupper(substr(self::getOperatingSystemName(), 0, 3)) === 'WIN';
    }

    /**
     * Return whether the operating system is Linux
     *
     * @return  bool
     */
    public static function isLinux()
    {
        return strtoupper(substr(self::getOperatingSystemName(), 0, 5)) === 'LINUX';
    }

    /**
     * Return whether the current environment is of type CLI
     *
     * @return  bool
     */
    public static function isCli()
    {
        if (PHP_SAPI == 'cli') {
            return true;
        } elseif ((PHP_SAPI == 'cgi' || PHP_SAPI == 'cgi-fcgi')
            && empty($_SERVER['SERVER_NAME'])) {
            return true;
        }

        return false;
    }

    /**
     * Return the operating system's name
     *
     * @return  string
     */
    public static function getOperatingSystemName()
    {
        return php_uname('s');
    }

    /**
     * Return the version of PHP
     *
     * @return  string
     */
    public static function getPhpVersion()
    {
        return phpversion();
    }

    /**
     * Return whether the given PHP module is loaded
     *
     * @param   string  $name   The name of the module to check
     *
     * @return  bool
     */
    public static function phpHasModule($name)
    {
        return extension_loaded($name);
    }

    /**
     * Return whether the given Zend framework class exists
     *
     * @param   string  $name   The name of the class to check
     *
     * @return  bool
     */
    public static function zendClassExists($name)
    {
        if (class_exists($name)) {
            return true;
        }

        return (@include str_replace('_', '/', $name) . '.php') !== false;
    }

    /**
     * Return the system's default timezone or null if it's not set
     *
     * @return  string|null
     *
     * @throws  Exception       In case date_default_timezone_get() emits anything but an E_WARNING
     */
    public static function getTimezone()
    {
        set_error_handler(function ($errno) { throw new Exception('thrown by date_default_timezone_get()', $errno); });

        try {
            $tz = date_default_timezone_get();
        } catch (Exception $e) {
            if ($e->getCode() !== E_WARNING) {
                restore_error_handler();
                throw $e;
            }

            $tz = null;
        }

        restore_error_handler();
        return $tz;
    }
}
// @codeCoverageIgnoreEnd
