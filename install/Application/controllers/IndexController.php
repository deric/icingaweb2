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

require_once 'Zend/Session.php';
require_once 'Zend/Controller/Action.php';
require_once realpath(__DIR__ . '/../forms/StartForm.php');
require_once realpath(__DIR__ . '/../forms/RequirementsForm.php');

use \Zend_Session;
use \Zend_Controller_Action;
use \Icinga\Installer\Wizard;
use \Icinga\Installer\Pages\StartForm;
use \Icinga\Installer\Pages\RequirementsForm;

/**
 * Installation index controller
 */
class IndexController extends Zend_Controller_Action
{
    /**
     * Generate the requirement report
     *
     * @return  array
     */
    private function generateReport()
    {
        $report = array();

        // Database extensions
        $foundMysqlExtension = extension_loaded('mysql');
        $foundPgsqlExtension = extension_loaded('pgsql');
        array_push(
            $report,
            array(
                'state'         => $foundMysqlExtension ? 1 : 0,
                'note'          => $foundMysqlExtension ? 'OK' : 'FAIL',
                'description'   => 'The MySQL php-extension is required to provide MySQL support'
            )
        );
        array_push(
            $report,
            array(
                'state'         => $foundPgsqlExtension ? 1 : 0,
                'note'          => $foundPgsqlExtension ? 'OK' : 'FAIL',
                'description'   => 'The PostgreSQL php-extension is required to provide PostgreSQL support'
            )
        );
        array_push(
            $report,
            array(
                'state'         => $foundMysqlExtension || $foundPgsqlExtension ? 1 : -1,
                'note'          => $foundMysqlExtension || $foundPgsqlExtension ? 'OK' : 'FAIL',
                'description'   => 'At least one database extension is required to install Icinga 2 Web'
            )
        );

        // Database adapters
        if (@include_once('Zend/Db/Adapter/Pdo/Mysql.php')) {
            $foundMysqlAdapter = true;
        } else {
            $foundMysqlAdapter = false;
        }
        if (@include_once('Zend/Db/Adapter/Pdo/Pgsql.php')) {
            $foundPgsqlAdapter = true;
        } else {
            $foundPgsqlAdapter = false;
        }
        array_push(
            $report,
            array(
                'state'         => $foundMysqlAdapter ? 1 : 0,
                'note'          => $foundMysqlAdapter ? 'OK' : 'FAIL',
                'description'   => 'The Zend db adapter for MySQL is required to provide support for MySQL'
            )
        );
        array_push(
            $report,
            array(
                'state'         => $foundPgsqlAdapter ? 1 : 0,
                'note'          => $foundPgsqlAdapter ? 'OK' : 'FAIL',
                'description'   => 'The Zend db adapter for PostgreSQL is required to provide support for PostgreSQL'
            )
        );
        array_push(
            $report,
            array(
                'state'         => $foundMysqlAdapter || $foundPgsqlAdapter ? 1 : -1,
                'note'          => $foundMysqlAdapter || $foundPgsqlAdapter ? 'OK' : 'FAIL',
                'description'   => 'At least one database adapter is required to install Icinga 2 Web'
            )
        );

        // LDAP extension
        array_push(
            $report,
            array(
                'state'         => extension_loaded('ldap') ? 1 : 0,
                'note'          => extension_loaded('ldap') ? 'OK' : 'FAIL',
                'description'   => 'The LDAP php-extension is required to provide AD authentication'
            )
        );

        // PHP
        $phpVersion = phpversion();
        $phpVersionMatched = preg_match('#5\.(3|4).*#', $phpVersion);
        array_push(
            $report,
            array(
                'state'         => $phpVersionMatched ? 1 : -1,
                'note'          => $phpVersionMatched ? 'OK' : 'FAIL',
                'description'   => 'Icinga 2 Web requires PHP version 5.3.x or 5.4.x'
            )
        );

        // Apache configuration
        $apacheConfigIsValid = $this->checkApacheConfig();
        if ($apacheConfigIsValid === null) {
            $description = 'The apache configuration could not be checked! Please check it'
                         . ' <a target="_blank" href="' . $this->view->baseUrl('configCheck') . '">manually</a>.';
            array_push(
                $report,
                array(
                    'state'         => 0,
                    'note'          => 'WARNING',
                    'description'   => $description,
                    'help'          => 'An internal server error (500) probably indicates'
                                       . ' that mod_rewrite is not enabled.'
                )
            );
        } else {
            array_push(
                $report,
                array(
                    'state'         => $apacheConfigIsValid ? 1 : -1,
                    'note'          => $apacheConfigIsValid ? 'OK' : 'FAIL',
                    'description'   => 'Icinga 2 Web requires that the use of .htaccess files is allowed',
                    'help'          => 'If this fails you might need to set AllowOverride appropriately or it'
                                       . ' indicates that mod_rewrite is not enabled in your environment.'
                )
            );
        }

        // Write access to the configuration directory
        $configDir = Wizard::getInstance()->getConfigurationDir();
        $configBase = $this->checkDirectoryAccess($configDir);
        $resources = $this->checkFileAccess($configDir . '/resources.ini');
        $authentication = $this->checkFileAccess($configDir . '/authentication.ini');
        $monitoringBase = $this->checkDirectoryAccess($configDir . '/modules/monitoring');
        $backends = $this->checkFileAccess($configDir . '/modules/monitoring/backends.ini');
        array_push(
            $report,
            array(
                'state'         => $configBase ? 1 : -1,
                'note'          => $configBase ? 'OK' : 'FAIL',
                'description'   => 'The directory config/ needs to be accessible by the PHP user'
            )
        );
        array_push(
            $report,
            array(
                'state'         => $resources ? 1 : -1,
                'note'          => $resources ? 'OK' : 'FAIL',
                'description'   => 'The file config/resources.ini needs to be accessible by the PHP user'
            )
        );
        array_push(
            $report,
            array(
                'state'         => $authentication ? 1 : -1,
                'note'          => $authentication ? 'OK' : 'FAIL',
                'description'   => 'The file config/authentication.ini needs to be accessible by the PHP user'
            )
        );
        array_push(
            $report,
            array(
                'state'         => $monitoringBase ? 1 : -1,
                'note'          => $monitoringBase ? 'OK' : 'FAIL',
                'description'   => 'The directory config/modules/monitoring needs to be accessible by the PHP user'
            )
        );
        array_push(
            $report,
            array(
                'state'         => $backends ? 1 : -1,
                'note'          => $backends ? 'OK' : 'FAIL',
                'description'   => 'The file config/modules/monitoring/backends.ini'
                                   . ' needs to be accessible by the PHP user'
            )
        );

        return $report;
    }

    /**
     * Return whether ../../configCheck/ is accessible
     *
     * @return  bool|null   Whether the configurations seems correct or cannot be checked
     */
    private function checkApacheConfig()
    {
        $hostUrl = 'http://' . $this->getRequest()->getHttpHost();
        $headers = @get_headers($hostUrl . $this->view->baseUrl('/configCheck/'));
        if ($headers) {
            $statusInfo = explode(' ', $headers[0]);
            return $statusInfo[1] === '403';
        }
    }

    /**
     * Return whether the given file is accessible
     *
     * @param   string  $path   The path to check
     * @return  bool            Whether the path is read- and writable
     */
    private function checkFileAccess($path)
    {
        $readHandle = @fopen($path, 'r');
        if (!$readHandle) {
            return false;
        }
        fclose($readHandle);
        $writeHandle = @fopen($path, 'a');
        if (!$writeHandle) {
            return false;
        }
        fclose($writeHandle);
        return true;
    }

    /**
     * Return whether the given directory is accessible
     *
     * @param   string  $path   The path to check
     * @return  bool            Whether the path is read- and writable
     */
    private function checkDirectoryAccess($path)
    {
        $dirInfo = stat($path);
        $authMode = $dirInfo['mode'] & 0777;
        if ($authMode & 0660 != 0660) {
            return false;
        }
        if (!@touch($path . '/.test')) {
            return false;
        } else {
            unlink($path . '/.test');
        }
        return true;
    }

    /**
     * Application wide action
     */
    public function indexAction()
    {
        Zend_Session::start();
        $this->view->currentStep = $this->_getParam('progress', 1);
        switch ($this->view->currentStep)
        {
            case 1:
                Zend_Session::namespaceUnset('installation');
                $this->showStart();
                break;
            case 2:
                $this->checkRequirements();
                break;
        }
    }

    /**
     * Show the starting page
     */
    private function showStart()
    {
        $this->view->form = new StartForm();
        $this->view->form->setRequest($this->getRequest());
    }

    /**
     * Report whether all requirements are fulfilled
     */
    private function checkRequirements()
    {
        $this->view->form = new RequirementsForm();
        $this->view->form->setRequest($this->getRequest());
        $this->view->form->setReport($this->generateReport());
    }
}
