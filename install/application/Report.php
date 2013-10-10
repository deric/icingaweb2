<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Installer;

use \ReflectionClass;
use \Zend_Controller_Front;
use \Icinga\Installer\Wizard;

/**
 * A class that collects and presents system information
 */
class Report
{
    /**
     * Whether the PHP MySQL extension is available
     *
     * @var bool
     */
    private $hasMysqlExtension;

    /**
     * Whether the PHP PostgreSQL extension is available
     *
     * @var bool
     */
    private $hasPgsqlExtension;

    /**
     * Whether the Zend MySQL adapter is available
     *
     * @var bool
     */
    private $hasMysqlAdapter;

    /**
     * Whether the Zend PostgreSQL adapter is available
     *
     * @var bool
     */
    private $hasPgsqlAdapter;

    /**
     * Wether the PHP LDAP extension is available
     *
     * @var bool
     */
    private $hasLdapExtension;

    /**
     * Whether the PHP version is correct
     *
     * @var bool
     */
    private $correctPhpVersion;

    /**
     * Whether the PHP short open tag is enabled
     *
     * @var bool
     */
    private $shortOpenTagEnabled;

    /**
     * Whether the apache configuration is valid
     *
     * @var bool
     */
    private $validApacheConfig;

    /**
     * An array that indicates which file- or directory-path is accessible
     *
     * @var array
     */
    private $pathAccess;

    /**
     * Return a new Report
     *
     * @param   ...     Optional arguments used when de-serializing
     */
    public function __construct()
    {
        $args = func_get_args();
        if (!empty($args)) {
            $this->hasMysqlExtension = $args[0];
            $this->hasPgsqlExtension = $args[1];
            $this->hasMysqlAdapter = $args[2];
            $this->hasPgsqlAdapter = $args[3];
            $this->hasLdapExtension = $args[4];
            $this->correctPhpVersion = $args[5];
            $this->shortOpenTagEnabled = $args[6];
            $this->validApacheConfig = $args[7];
            $this->pathAccess = $args[8];
        } else {
            $this->init();
        }
    }

    /**
     * Return an instance of Report constructed from the given JSON
     *
     * @param   string   $json   The serialized representation of a report
     * @return  Report
     */
    static public function fromJSON($json)
    {
        $data = json_decode($json, true);
        $ref = new ReflectionClass(__CLASS__);
        return $ref->newInstanceArgs(
            array(
                $data['hasMysqlExtension'],
                $data['hasPgsqlExtension'],
                $data['hasMysqlAdapter'],
                $data['hasPgsqlAdapter'],
                $data['hasLdapExtension'],
                $data['correctPhpVersion'],
                $data['shortOpenTagEnabled'],
                $data['validApacheConfig'],
                $data['pathAccess']
            )
        );
    }

    /**
     * Return the report as JSON
     *
     * @return  string           The serialized representation of a report
     */
    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    /**
     * Return the current information as associative array
     *
     * @return  array
     */
    public function toArray()
    {
        return array(
            'hasMysqlExtension'     => $this->hasMysqlExtension,
            'hasPgsqlExtension'     => $this->hasPgsqlExtension,
            'hasMysqlAdapter'       => $this->hasMysqlAdapter,
            'hasPgsqlAdapter'       => $this->hasPgsqlAdapter,
            'hasLdapExtension'      => $this->hasLdapExtension,
            'correctPhpVersion'     => $this->correctPhpVersion,
            'shortOpenTagEnabled'   => $this->shortOpenTagEnabled,
            'validApacheConfig'     => $this->validApacheConfig,
            'pathAccess'            => $this->pathAccess
        );
    }

    /**
     * Initialise this report and collect all necessary information
     */
    private function init()
    {
        $this->hasMysqlExtension = extension_loaded('mysql');
        $this->hasPgsqlExtension = extension_loaded('pgsql');
        $this->hasMysqlAdapter = @include_once('Zend/Db/Adapter/Pdo/Mysql.php');
        $this->hasPgsqlAdapter = @include_once('Zend/Db/Adapter/Pdo/Pgsql.php');
        $this->hasLdapExtension = extension_loaded('ldap');
        $this->correctPhpVersion = preg_match('#5\.(3|4).*#', phpversion());
        $this->shortOpenTagEnabled = ini_get('short_open_tag') || preg_match('#5\.4.*#', phpversion());

        $status = $this->getStatusCode('configCheck/');
        $this->validApacheConfig = $status === null ? null : $status === 403;

        $configDir = Wizard::getInstance()->getConfigurationDir();
        $this->pathAccess = array(
            'config' => is_readable($configDir) && is_writable($configDir)
        );
    }

    /**
     * Return the resulting http status code when accessing the given path
     *
     * @param   string      $path   The path relative to the current location
     * @return  int|null            The status code or null if no connection was possible
     */
    private function getStatusCode($path)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $baseUrl = 'http://' . $request->getHttpHost() . $request->getBaseUrl();
        $headers = @get_headers(implode('/', array($baseUrl, $path)));
        if ($headers) {
            $statusInfo = explode(' ', $headers[0]);
            return intval($statusInfo[1]);
        }
    }

    /**
     * Return a textual representation of the current information
     *
     * @return  array
     */
    private function generateContent()
    {
        $content = array();

        array_push(
            $content,
            array(
                'state' => $this->hasMysqlExtension ? 1 : 0,
                'note'  => $this->hasMysqlExtension ? 'OK' : 'WARNING',
                'desc'  => 'The MySQL php-extension is required to provide MySQL support'
            )
        );
        array_push(
            $content,
            array(
                'state' => $this->hasPgsqlExtension ? 1 : 0,
                'note'  => $this->hasPgsqlExtension ? 'OK' : 'WARNING',
                'desc'  => 'The PostgreSQL php-extension is required to provide PostgreSQL support'
            )
        );

        if (!$this->hasMysqlExtension && !$this->hasPgsqlExtension) {
            array_push(
                $content,
                array(
                    'state' => -1,
                    'note'  => 'FAIL',
                    'desc'  => 'At least one database extension is required to install Icinga 2 Web'
                )
            );
        }

        array_push(
            $content,
            array(
                'state' => $this->hasMysqlAdapter ? 1 : 0,
                'note'  => $this->hasMysqlAdapter ? 'OK' : 'WARNING',
                'desc'  => 'The Zend db adapter for MySQL is required to provide support for MySQL'
            )
        );
        array_push(
            $content,
            array(
                'state' => $this->hasPgsqlAdapter ? 1 : 0,
                'note'  => $this->hasPgsqlAdapter ? 'OK' : 'WARNING',
                'desc'  => 'The Zend db adapter for PostgreSQL is required to provide support for PostgreSQL'
            )
        );

        if (!$this->hasMysqlAdapter && !$this->hasPgsqlAdapter) {
            array_push(
                $content,
                array(
                    'state' => -1,
                    'note'  => 'FAIL',
                    'desc'  => 'At least one database adapter is required to install Icinga 2 Web'
                )
            );
        }

        if ($this->hasMysqlAdapter && !$this->hasMysqlExtension &&
            !($this->hasPgsqlAdapter && $this->hasPgsqlExtension)) {
            array_push(
                $content,
                array(
                    'state' => -1,
                    'note'  => 'FAIL',
                    'desc'  => 'The MySQL php-extension is required to use the respective Zend db adapter'
                )
            );
        }

        if ($this->hasPgsqlAdapter && !$this->hasPgsqlExtension &&
            !($this->hasMysqlAdapter && $this->hasMysqlExtension)) {
            array_push(
                $content,
                array(
                    'state' => -1,
                    'note'  => 'FAIL',
                    'desc'  => 'The PostgreSQL php-extension is required to use the respective Zend db adapter'
                )
            );
        }

        array_push(
            $content,
            array(
                'state' => $this->hasLdapExtension ? 1 : 0,
                'note'  => $this->hasLdapExtension ? 'OK' : 'WARNING',
                'desc'  => 'The LDAP php-extension is required to provide AD authentication'
            )
        );
        array_push(
            $content,
            array(
                'state' => $this->correctPhpVersion ? 1 : -1,
                'note'  => $this->correctPhpVersion ? 'OK' : 'FAIL',
                'desc'  => 'Icinga 2 Web requires PHP version 5.3.x or 5.4.x'
            )
        );
        array_push(
            $content,
            array(
                'state' => $this->shortOpenTagEnabled ? 1 : 0,
                'note'  => $this->shortOpenTagEnabled ? 'OK' : 'WARNING',
                'desc'  => 'Icinga 2 Web makes use of the PHP short open tag &lt;?='
            )
        );

        if ($this->validApacheConfig === null) {
            array_push(
                $content,
                array(
                    'state' => 0,
                    'note'  => 'WARNING',
                    'desc'  => 'The apache configuration could not be checked! Please check it'
                             . ' <a target="_blank" href="configCheck/">manually</a>.',
                    'help'  => 'An internal server error (500) probably indicates that mod_rewrite is not enabled.'
                )
            );
        } else {
            array_push(
                $content,
                array(
                    'state' => $this->validApacheConfig ? 1 : -1,
                    'note'  => $this->validApacheConfig ? 'OK' : 'FAIL',
                    'desc'  => 'Icinga 2 Web requires that the use of .htaccess files is allowed',
                    'help'  => 'If this fails you might need to set AllowOverride appropriately or it'
                             . ' indicates that mod_rewrite is not enabled in your environment.'
                )
            );
        }

        array_push(
            $content,
            array(
                'state' => $this->pathAccess['config'] ? 1 : -1,
                'note'  => $this->pathAccess['config'] ? 'OK' : 'FAIL',
                'desc'  => 'The configuration directory needs to be read-/writable by the PHP user'
            )
        );

        return $content;
    }

    /**
     * Return whether this report's information is sufficient
     *
     * @return  bool    Whether the information is sufficient
     */
    public function isOk()
    {
        if (!($this->hasMysqlExtension || $this->hasPgsqlExtension) ||
            !($this->hasMysqlAdapter || $this->hasPgsqlAdapter) ||
            ($this->hasMysqlAdapter && !$this->hasMysqlExtension &&
             !($this->hasPgsqlAdapter && $this->hasPgsqlExtension)) ||
            ($this->hasPgsqlAdapter && !$this->hasPgsqlExtension &&
             !($this->hasMysqlAdapter && $this->hasMysqlExtension)) ||
            !$this->correctPhpVersion || $this->validApacheConfig === false) {
            return false;
        }

        foreach ($this->pathAccess as $name => $state) {
            if (!$state) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return this report rendered as HTML
     *
     * @return  string
     */
    public function render()
    {
        $tableBody = '';
        foreach ($this->generateContent() as $requirementInfo) {
            if ($requirementInfo['state'] === -1) {
                $tableBody .= '<tr class="danger">';
            } elseif ($requirementInfo['state'] === 0) {
                $tableBody .= '<tr class="warning">';
            } else {
                $tableBody .= '<tr class="success">';
            }

            $helpText = '';
            if (isset($requirementInfo['help']) && $requirementInfo['help']) {
                $helpText = '<br /><span style="font-size: .8em">' . $requirementInfo['help'] . '</span>';
            }
            $tableBody .= '<td>' . $requirementInfo['desc'] . $helpText . '</td>';
            $tableBody .= '<td>' . $requirementInfo['note'] . '</td>';
            $tableBody .= '</tr>';
        }

        return '<table class="table">'
          . '  <thead>'
          . '    <tr>'
          . '      <th>Requirement</th>'
          . '      <th>State</th>'
          . '    </tr>'
          . '  </thead>'
          . '  <tbody>'
          . $tableBody
          . '  </tbody>'
          . '</table>';
    }
}
