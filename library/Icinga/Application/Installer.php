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

namespace Icinga\Application;

use \Exception;
use \Zend_Config;
use \Icinga\Config\PreservingIniWriter;

class Installer
{
    /**
     * The configuration directory to use
     *
     * @var string
     */
    private $configDir;

    /**
     * The installation options to use
     *
     * @var Zend_Config
     */
    private $options;

    /**
     * The installation "log"
     *
     * @var array
     */
    private $result;

    /**
     * Whether the installation failed
     *
     * @var bool
     */
    private $failed;

    /**
     * Initialise a new icingaweb installer
     *
     * @param   string          $configDir  The configuration directory to use
     * @param   Zend_Config     $options    The installation options to use
     */
    public function __construct($configDir, Zend_Config $options)
    {
        $this->configDir = $configDir;
        $this->options = $options;
        $this->result = array();
        $this->failed = false;
    }

    /**
     * Get the installation result
     *
     * @return  array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Return whether the installation failed
     *
     * @return  bool
     */
    public function hasFailed()
    {
        return $this->failed;
    }

    /**
     * Run the installation
     *
     * @return  array
     */
    public function run()
    {
        try {
            $this->setupResources();
            $this->setupAuthentication();
            $this->setupPreferences();
            $this->setupBackend();
            $this->setupDatabase();
            $this->setupDefaultAdmin();
        } catch (Exception $error) {
            // TODO: Log this exception? (To the logfile, not $this->log()!)
            $this->failed = true;
            return;
        }

        $this->finalize();
    }

    /**
     * Log a result message
     *
     * @param   string  $section    The section/title for which the message is for
     * @param   string  $message    The message
     */
    private function log($section, $message)
    {
        if (!array_key_exists($section, $this->result)) {
            $this->result[$section] = array();
        }

        array_push($this->result[$section], $message);
    }

    /**
     * Set up the resources.ini
     */
    private function setupResources()
    {
        $configPath = $this->configDir . '/resources.ini';
        $templatePath = $this->configDir . '/resources.ini.in';
        if (!$this->cleanAutoconfIni($templatePath)) {
            $message = 'Could not duplicate INI template: ' . $templatePath;
            $this->log('Resource configuration', $message);
            throw new Exception($message);
        }
        chmod($configPath, 0664);
        $this->log('Resource configuration', 'Successfully duplicated INI template: ' . $templatePath);

        $dbConfig = $this->options->dbConfig;
        $iniContent = array(
            $dbConfig->db_resource => array(
                'type'      => 'db',
                'db'        => $dbConfig->db_provider,
                'host'      => $dbConfig->db_host,
                'dbname'    => $dbConfig->db_name,
                'username'  => $dbConfig->db_username,
                'password'  => $dbConfig->db_password
            )
        );
        if (!empty($dbConfig->db_port)) {
            $iniContent[$dbConfig->db_resource]['port'] = $dbConfig->db_port;
        }
        $this->log('Resource configuration', 'Added primary database store: ' . $dbConfig->db_resource);

        $backendConfig = $this->options->backendConfig;
        if ($backendConfig->backend_ido_host !== null) {
            $iniContent[$backendConfig->backend_name] = array(
                'type'      => 'db',
                'db'        => $backendConfig->backend_ido_provider,
                'host'      => $backendConfig->backend_ido_host,
                'dbname'    => $backendConfig->backend_ido_dbname,
                'username'  => $backendConfig->backend_ido_dbuser,
                'password'  => $backendConfig->backend_ido_dbpass
            );
            if (!empty($backendConfig->backend_ido_port)) {
                $iniContent[$backendConfig->backend_name]['port'] = $backendConfig->backend_ido_port;
            }
            $this->log('Resource configuration', 'Added IDO database store: ' . $backendConfig->backend_name);
        }

        $iniWriter = new PreservingIniWriter(
            array(
                'config'    => new Zend_Config($iniContent),
                'filename'  => $configPath
            )
        );

        try {
            $iniWriter->write();
        } catch (Exception $error) {
            $this->log('Resource configuration', 'Error while writing INI file: ' . $error->getMessage());
            throw $error;
        }

        $this->log('Resource configuration', 'Configuration successfully written to: ' . $configPath);
    }

    /**
     * Set up the authentication.ini
     */
    private function setupAuthentication()
    {
        $configPath = $this->configDir . '/authentication.ini';
        $templatePath = $this->configDir . '/authentication.ini.in';
        if (!$this->cleanAutoconfIni($templatePath)) {
            $message = 'Could not duplicate INI template: ' . $templatePath;
            $this->log('Authentication configuration', $message);
            throw new Exception($message);
        }
        chmod($configPath, 0664);
        $this->log('Authentication configuration', 'Successfully duplicated INI template: ' . $templatePath);

        $iniContent = array();
        $authConfig = $this->options->authConfig;

        if ($authConfig->auth_use_ldap) {
            $iniContent['ldap_authentication'] = array(
                'hostname'              => $authConfig->auth_ldap_hostname,
                'root_dn'               => $authConfig->auth_ldap_root_dn,
                'bind_dn'               => $authConfig->auth_ldap_bind_dn,
                'bind_pw'               => $authConfig->auth_ldap_bind_pw,
                'user_class'            => $authConfig->auth_ldap_user_class,
                'user_name_attribute'   => $authConfig->auth_ldap_user_name_attributes,
                'target'                => 'user',
                'backend'               => 'ldap'
            );
            $this->log('Authentication configuration', 'Added LDAP authentication backend: ldap_authentication');
        }

        $iniContent['internal_authentication'] = array(
            'resource'  => $this->options->dbConfig->db_resource,
            'target'    => 'user',
            'backend'   => 'db'
        );
        $this->log('Authentication configuration', 'Added db authentication backend: internal_authentication');

        $iniWriter = new PreservingIniWriter(
            array(
                'config'    => new Zend_Config($iniContent),
                'filename'  => $configPath
            )
        );

        try {
            $iniWriter->write();
        } catch (Exception $error) {
            $this->log('Authentication configuration', 'Error while writing INI file: ' . $error->getMessage());
            throw $error;
        }

        $this->log('Authentication configuration', 'Configuration successfully written to: ' . $configPath);
    }

    /**
     * Set up the preferences (config.ini)
     */
    private function setupPreferences()
    {
        $configPath = $this->configDir . '/config.ini';

        try {
            $config = new Config($configPath);
        } catch (Exception $error) {
            $this->log('Preference configuration', 'Error while reading INI file: ' . $error->getMessage());
            throw $error;
        }

        $preferenceType = $this->options->authConfig->auth_preference_store;
        if ($preferenceType === 'type_ini') {
            $config->preferences->type = 'ini';
        } elseif ($preferenceType === 'type_db') {
            $config->preferences->type = 'db';
            $config->preferences->resource = $this->options->dbConfig->db_resource;
        } elseif (isset($config->preferences)) { // $preferenceType === 'type_none'
            $config->preferences->type = 'null';
        }

        $iniWriter = new PreservingIniWriter(
            array(
                'filename'  => $configPath,
                'config'    => $config
            )
        );

        try {
            $iniWriter->write();
        } catch (Exception $error) {
            $this->log('Preference configuration', 'Error while writing INI file: ' . $error->getMessage());
            throw $error;
        }

        $this->log('Preference configuration', 'Configuration successfully written to: ' . $configPath);
    }

    /**
     * Set up the initial backend
     */
    private function setupBackend()
    {
        
    }

    /**
     * Set up the database structure
     */
    private function setupDatabase()
    {
        
    }

    /**
     * Set up the default admin user
     */
    private function setupDefaultAdmin()
    {
        
    }

    /**
     * Do any finalization steps
     */
    private function finalize()
    {
        
    }

    /**
     * Copy the given INI template to its destination file and strip all autoconf template strings
     *
     * @param   string  $path   The path to the template
     * @return  bool            Whether the operation succeeded
     */
    private function cleanAutoconfIni($path)
    {
        $content = @file_get_contents($path);
        if ($content) {
            if (@file_put_contents(substr($path, 0, -3), preg_replace('#@[a-z_]+@#', '', $content))) {
                return true;
            }
        }

        return false;
    }
}
