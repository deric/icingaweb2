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
