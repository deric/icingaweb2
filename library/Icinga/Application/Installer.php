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
            $this->setupDefaultAdmin();
            $this->setupBackend();
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
        if (!@copy($templatePath, $configPath)) {
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
                'filename'  => $configPath,
                'config'    => new Zend_Config($iniContent)
            )
        );

        try {
            $iniWriter->write();
        } catch (Exception $error) {
            $this->log('Resource configuration', 'Error while writing INI file: ' . $error->getMessage());
            throw $error;
        }

        $this->log(
            'Resource configuration',
            'Configuration successfully written to: ' . $configPath
        );
    }

    /**
     * Set up the authentication.ini
     */
    private function setupAuthentication()
    {
        
    }

    /**
     * Set up the preferences (config.ini)
     */
    private function setupPreferences()
    {
        
    }

    /**
     * Set up the default admin user
     */
    private function setupDefaultAdmin()
    {
        
    }

    /**
     * Set up the initial backend
     */
    private function setupBackend()
    {
        
    }

    /**
     * Do any finalization steps
     */
    private function finalize()
    {
        
    }
}
