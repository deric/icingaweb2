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

require_once 'Zend/Form.php';
require_once 'Zend/Form/Element/Xhtml.php';
require_once 'Zend/Form/Element/Submit.php';
require_once 'Zend/Form/Decorator/Abstract.php';
require_once realpath(__DIR__ . '/../../../library/Icinga/Web/Form.php');
require_once realpath(__DIR__ . '/../../../library/Icinga/Web/Form/Element/Note.php');
require_once realpath(__DIR__ . '/../../../library/Icinga/Web/Form/Decorator/HelpText.php');
require_once realpath(__DIR__ . '/../../../library/Icinga/Web/Form/Decorator/BootstrapForm.php');

use \Icinga\Web\Form;
use \Icinga\Installer\Report;
use \Zend_Session_Namespace;

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
     * Mark the current page to restart the wizard on submit
     */
    public function restartWizard()
    {
        $this->setProgress(1);
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
}
