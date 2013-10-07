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
require_once 'Zend/Session/Namespace.php';
require_once 'Zend/Controller/Action.php';
require_once realpath(__DIR__ . '/../Report.php');
require_once realpath(__DIR__ . '/../forms/WizardForm.php');
require_once realpath(__DIR__ . '/../forms/StartForm.php');
require_once realpath(__DIR__ . '/../forms/DbConfigForm.php');
require_once realpath(__DIR__ . '/../forms/RequirementsForm.php');

use \Zend_Session;
use \Zend_Session_Namespace;
use \Zend_Controller_Action;
use \Icinga\Installer\Report;
use \Icinga\Installer\Pages\StartForm;
use \Icinga\Installer\Pages\DbConfigForm;
use \Icinga\Installer\Pages\RequirementsForm;

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
                if ($this->validateConfirmation($namespace)) {
                    $this->runInstallation();
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
    private function checkRequirements($namespace)
    {
        $report = new Report();
        $this->view->form = new RequirementsForm();
        $this->view->form->setRequest($this->getRequest());
        $this->view->form->setReport($report);
        if ($report->isOk()) {
            $this->view->form->advanceToNextPage();
        } else {
            $this->view->form->restartWizard();
        }
        $namespace->report = $report->toJSON();
    }

    /**
     * Prompt the user for database details
     */
    private function getDatabaseDetails($namespace)
    {
        $this->view->form = new DbConfigForm();
        $this->view->form->setRequest($this->getRequest());
        $this->view->form->setReport(Report::fromJSON($namespace->report));
        $this->view->form->advanceToNextPage();
    }

    /**
     * Validate the given database details
     *
     * @return  bool    Whether the details are valid
     * @todo            Validate db connection and auth
     */
    private function validateDatabaseDetails($namespace)
    {
        $form = new DbConfigForm();
        $form->setRequest($this->getRequest());
        $form->setReport(Report::fromJSON($namespace->report));

        if (!$form->isSubmittedAndValid()) {
            $this->view->form = $form;
            $form->stayOnPage();
            return false;
        }
        return true;
    }

    /**
     * Prompt the user for authentication details
     */
    private function getAuthenticationDetails()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Validate the given authentication details
     *
     * @return  bool    Whether the details are valid
     */
    private function validateAuthenticationDetails()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Prompt the user for backend details
     */
    private function getBackendDetails()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Validate the given backend details
     *
     * @return  bool    Whether the details are valid
     */
    private function validateBackendDetails()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Prompt the user to confirm all the provided details
     */
    private function getConfirmation()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Validate the confirmed install
     *
     * @return  bool    Whether the confirmation is valid
     */
    private function validateConfirmation()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Process the entire details and run the installation
     */
    private function runInstallation()
    {
        throw new \Exception('Not implemented');
    }
}
