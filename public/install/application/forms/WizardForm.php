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

namespace Icinga\Installer\Pages;

require_once 'Zend/Db.php';
require_once 'Zend/Form.php';
require_once 'Zend/Config.php';
require_once 'Zend/Form/Element/Xhtml.php';
require_once 'Zend/Form/Element/Submit.php';
require_once 'Zend/Form/Decorator/Abstract.php';
require_once realpath(__DIR__ . '/../validators/PasswordValidator.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Web/Form.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Web/Form/Element/Note.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Web/Form/Decorator/HelpText.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Web/Form/Decorator/BootstrapForm.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Util/ConfigAwareFactory.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Application/DbAdapterFactory.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Authentication/UserBackend.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Authentication/Backend/LdapUserBackend.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Protocol/Ldap/Connection.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Protocol/Ldap/LdapUtils.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Protocol/Ldap/Query.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Data/DatasourceInterface.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Protocol/Statusdat/IReader.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Protocol/Statusdat/Reader.php');
require_once realpath(__DIR__ . '/../../../../library/Icinga/Exception/ConfigurationError.php');

use \Exception;
use \Zend_Config;
use \Zend_Session_Namespace;
use \Icinga\Web\Form;
use \Icinga\Web\Form\Element\Note;
use \Icinga\Installer\Report;
use \Icinga\Application\DbAdapterFactory;
use \Icinga\Authentication\Backend\LdapUserBackend;
use \Icinga\Protocol\Statusdat\Reader as StatusdatReader;

/**
 * Base form for every wizard page
 */
class WizardForm extends Form
{
    /**
     * The system report
     *
     * @var Report
     */
    private $report;

    /**
     * The user's session
     *
     * @var Zend_Session_Namespace
     */
    private $session;

    /**
     * Last used error note id
     *
     * @var int
     */
    private $lastErrorNoteId = 0;

    /**
     * Set the system report to use
     *
     * @param   Report   $report     The report to use
     */
    public function setReport(Report $report)
    {
        $this->report = $report;
    }

    /**
     * Return the currently used system report
     *
     * @return  Report              The current system report
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * Set the user's session
     *
     * @param   Zend_Session_Namespace  $namespace  The user's session
     */
    public function setSession(Zend_Session_Namespace $namespace)
    {
        $this->session = $namespace;
    }

    /**
     * Return the user's session
     *
     * @return  Zend_Session_Namespace
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Mark the current page to advance to the next one
     */
    public function advanceToNextPage()
    {
        $this->setProgress($this->getRequest()->getPost('progress', 1) + 1);
    }

    /**
     * Mark the current page to advance not to the next one
     */
    public function stayOnPage()
    {
        $this->setProgress($this->getRequest()->getPost('progress', 1));
    }

    /**
     * Set the progress of the wizard
     *
     * @param   int     $step   The current wizard step
     */
    public function setProgress($step)
    {
        $this->addElement(
            'hidden',
            'progress',
            array(
                'value' => $step
            )
        );
    }

    /**
     * Get the current wizard step
     *
     * @return  int
     */
    public function getCurrentStep()
    {
        $step = $this->getValue('progress');
        if ($step === null) {
            $step = $this->getRequest()->getPost('progress', 1);
        } else {
            $step -= 1;
        }
        return $step;
    }

    /**
     * Return a list of available database providers
     *
     * @return  array
     */
    public function getDatabaseProviders()
    {
        $reportInfo = $this->getReport()->toArray();
        $providers = array();

        if ($reportInfo['hasMysqlExtension'] && $reportInfo['hasMysqlAdapter']) {
            $providers['mysql'] = 'MySQL';
        }
        if ($reportInfo['hasPgsqlExtension'] && $reportInfo['hasPgsqlAdapter']) {
            $providers['pgsql'] = 'PostgreSQL';
        }

        return $providers;
    }

    /**
     * Return a list of available preference stores
     *
     * @return  array
     */
    public function getPreferenceStores()
    {
        return array(
            'type_ini'  => 'File system: INI files',
            'type_db'   => 'Database: ' . $this->getSession()->databaseDetails['db_resource'],
            'type_none' => 'Disable storage of user preferences across sessions'
        );
    }

    /**
     * Return a list of available resources
     *
     * @return  array
     */
    public function getResources()
    {
        return array(
            $this->getSession()->databaseDetails['db_resource']
        );
    }

    /**
     * Add an error note at a specific location to the form
     *
     * @param   string  $message    The message to display
     * @param   int     $position   Where to display the message
     */
    public function addErrorNote($message, $position)
    {
        $this->addElement(
            new Note(
                array(
                    'escape'    => true,
                    'order'     => $position + 1, // +1 due to the hidden progress element
                    'name'      => sprintf('error_note_%s', $this->lastErrorNoteId++),
                    'value'     => sprintf('<span style="color:red">%s</span>', $message)
                )
            )
        );
    }

    /**
     * Check whether a database connection can be established
     *
     * @param   Zend_Config     $config     The database connection details to use
     * @return  string                      OK in case a connection has been established, otherwise the error message
     */
    public function checkDatabaseConnection(Zend_Config $config)
    {
        $db = DbAdapterFactory::createDbAdapter($config);

        try {
            $db->getConnection();
        } catch (Exception $error) {
            $errorMessage = $error->getMessage();
        }

        $succeeded = $db->isConnected();
        $db->closeConnection();
        return $succeeded ? 'OK' : $errorMessage;
    }

    /**
     * Check whether it is possible to authenticate using LDAP with the given connection details
     *
     * @param   Zend_Config     $config     The LDAP connection details to use
     * @return  string                      OK in case the connection was successful, otherwise the error message
     */
    public function checkLdapAuthentication(Zend_Config $config)
    {
        try {
            $conn = new LdapUserBackend($config);
            $conn->getUserCount();
        } catch (Exception $error) {
            return $error->getMessage();
        }

        return 'OK';
    }

    /**
     * Check the existence of the given paths and whether their content is valid
     *
     * @param   Zend_Config     $config     The protocol information to use
     * @return  string                      OK in case both paths are valid, otherwise an error message
     */
    public function checkStatusDat(Zend_Config $config)
    {
        try {
            new StatusdatReader($config);
        } catch (Exception $error) {
            return $error->getMessage();
        }

        return 'OK';
    }

    /**
     * Check whether the given path points to a valid Livestatus socket
     *
     * @param   string  $socketPath     The path to the Livestatus socket
     * @return  string                  OK in case the socket is valid, otherwise the error message
     * @todo                            Implement validation logic #4832
     */
    public function checkLiveStatus($socketPath)
    {
        return 'OK';
    }
}
