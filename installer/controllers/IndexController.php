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

require_once 'Zend/Config.php';
require_once 'Zend/Session.php';
require_once 'Zend/Config/Ini.php';
require_once 'Zend/Session/Namespace.php';
require_once 'Zend/Controller/Action.php';
require_once 'Zend/Config/Writer/FileAbstract.php';
require_once realpath(__DIR__ . '/../forms/WizardForm.php');
require_once realpath(__DIR__ . '/../forms/EndForm.php');
require_once realpath(__DIR__ . '/../forms/StartForm.php');
require_once realpath(__DIR__ . '/../forms/DbConfigForm.php');
require_once realpath(__DIR__ . '/../forms/AuthConfigForm.php');
require_once realpath(__DIR__ . '/../forms/RequirementsForm.php');
require_once realpath(__DIR__ . '/../forms/ConfirmationForm.php');
require_once realpath(__DIR__ . '/../forms/BackendConfigForm.php');
require_once realpath(__DIR__ . '/../../library/Icinga/Util/Report.php');
require_once realpath(__DIR__ . '/../../library/Icinga/Config/IniEditor.php');
require_once realpath(__DIR__ . '/../../library/Icinga/Config/PreservingIniWriter.php');
require_once realpath(__DIR__ . '/../../library/Icinga/Application/Installer.php');

use \Zend_Config;
use \Zend_Session;
use \Zend_Session_Namespace;
use \Zend_Controller_Action;
use \Icinga\Util\Report;
use \Icinga\Application\Wizard;
use \Icinga\Application\Installer;
use \Icinga\Installer\Pages\EndForm;
use \Icinga\Installer\Pages\StartForm;
use \Icinga\Installer\Pages\DbConfigForm;
use \Icinga\Installer\Pages\AuthConfigForm;
use \Icinga\Installer\Pages\RequirementsForm;
use \Icinga\Installer\Pages\ConfirmationForm;
use \Icinga\Installer\Pages\BackendConfigForm;

/**
 * Installation index controller
 */
class IndexController extends Zend_Controller_Action
{
    /**
     * Application wide action
     */
    public function indexAction()
    {
        $namespace = new Zend_Session_Namespace('installation');
        switch ($this->_getParam('progress', 1))
        {
            case 1:
                Zend_Session::namespaceUnset('installation');
                $this->showStart();
                break;
            case 2:
                $this->checkRequirements($namespace);
                break;
            case 3:
                $this->getDatabaseDetails($namespace);
                break;
            case 4:
                if ($this->validateDatabaseDetails($namespace)) {
                    $this->getAuthenticationDetails($namespace);
                }
                break;
            case 5:
                if ($this->validateAuthenticationDetails($namespace)) {
                    $this->getBackendDetails($namespace);
                }
                break;
            case 6:
                if ($this->validateBackendDetails($namespace)) {
                    $this->getConfirmation($namespace);
                }
                break;
            case 7:
                if ($this->validateConfirmation($namespace) && $this->runInstallation($namespace)) {
                    Zend_Session::namespaceUnset('installation');
                }
        }
    }

    /**
     * Show the starting page
     */
    private function showStart()
    {
        $this->view->form = new StartForm();
        $this->view->form->setRequest($this->getRequest());
        $this->view->form->advanceToNextPage();
    }

    /**
     * Report whether all requirements are fulfilled
     */
    private function checkRequirements($session)
    {
        $report = new Report();
        $this->view->form = new RequirementsForm();
        $this->view->form->setRequest($this->getRequest());
        $this->view->form->setReport($report);
        if ($report->isOk()) {
            $this->view->form->advanceToNextPage();
        } else {
            $this->view->form->stayOnPage();
        }
        $session->report = $report->toJSON();
    }

    /**
     * Prompt the user for database details
     */
    private function getDatabaseDetails($session)
    {
        $this->view->form = new DbConfigForm();
        $this->view->form->setRequest($this->getRequest());
        $this->view->form->setReport(Report::fromJSON($session->report));
        $this->view->form->advanceToNextPage();
    }

    /**
     * Validate the given database details
     *
     * @return  bool    Whether the details are valid
     */
    private function validateDatabaseDetails($session)
    {
        $form = new DbConfigForm();
        $form->setRequest($this->getRequest());
        $form->setReport(Report::fromJSON($session->report));

        if (!$form->isSubmittedAndValid()) {
            $this->view->form = $form;
            $form->stayOnPage();
            return false;
        }

        $session->databaseDetails = $form->getDetails();
        return true;
    }

    /**
     * Prompt the user for authentication details
     */
    private function getAuthenticationDetails($session)
    {
        $this->view->form = new AuthConfigForm();
        $this->view->form->setSession($session);
        $this->view->form->setRequest($this->getRequest());
        $this->view->form->setReport(Report::fromJSON($session->report));
        $this->view->form->advanceToNextPage();
    }

    /**
     * Validate the given authentication details
     *
     * @return  bool    Whether the details are valid
     */
    private function validateAuthenticationDetails($session)
    {
        $form = new AuthConfigForm();
        $form->setSession($session);
        $form->setRequest($this->getRequest());
        $form->setReport(Report::fromJSON($session->report));

        if (!$form->isSubmittedAndValid()) {
            $this->view->form = $form;
            $form->stayOnPage();
            return false;
        }

        $session->authenticationDetails = $form->getDetails();
        return true;
    }

    /**
     * Prompt the user for backend details
     */
    private function getBackendDetails($session)
    {
        $this->view->form = new BackendConfigForm();
        $this->view->form->setSession($session);
        $this->view->form->setRequest($this->getRequest());
        $this->view->form->setReport(Report::fromJSON($session->report));
        $this->view->form->advanceToNextPage();
    }

    /**
     * Validate the given backend details
     *
     * @return  bool    Whether the details are valid
     */
    private function validateBackendDetails($session)
    {
        $form = new BackendConfigForm();
        $form->setSession($session);
        $form->setRequest($this->getRequest());
        $form->setReport(Report::fromJSON($session->report));

        if (!$form->isSubmittedAndValid()) {
            $this->view->form = $form;
            $form->stayOnPage();
            return false;
        }

        $session->backendDetails = $form->getDetails();
        return true;
    }

    /**
     * Prompt the user to confirm all the provided details
     */
    private function getConfirmation($session)
    {
        $this->view->form = new ConfirmationForm();
        $this->view->form->setSession($session);
        $this->view->form->setRequest($this->getRequest());
        $this->view->form->setReport(Report::fromJSON($session->report));
        $this->view->form->advanceToNextPage();
    }

    /**
     * Validate the confirmed install
     *
     * @return  bool    Whether the confirmation is valid
     */
    private function validateConfirmation($session)
    {
        $form = new ConfirmationForm();
        $form->setSession($session);
        $form->setRequest($this->getRequest());
        $form->setReport(Report::fromJSON($session->report));
        return $form->isSubmittedAndValid();
    }

    /**
     * Process the entire details and run the installation
     *
     * @return  bool    Whether the installation succeeded
     */
    private function runInstallation($session)
    {
        $installer = new Installer(
            Wizard::getInstance()->getConfigurationDir(),
            new Zend_Config(
                array(
                    'backendConfig' => $session->backendDetails,
                    'dbConfig'      => $session->databaseDetails,
                    'authConfig'    => $session->authenticationDetails
                )
            )
        );
        $installer->run();

        $this->view->form = new EndForm();
        $this->view->form->setRequest($this->getRequest());
        $this->view->form->setResult($installer->getResult());

        if ($installer->hasFailed()) {
            $this->view->form->retryInstallation();
            return false;
        } else {
            $this->view->form->finishInstallation();
            return true;
        }
    }
}
