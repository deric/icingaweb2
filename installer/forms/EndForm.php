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

/**
 * Wizard-Page that shows the user an installation result/report
 */
class EndForm extends WizardForm
{
    /**
     * Whether the installation succeeded
     *
     * @var bool
     */
    private $success;

    /**
     * The result to present
     *
     * @var array
     */
    private $result;

    /**
     * Set the result that is shown to the user
     *
     * @param   array   $result     The result to show
     */
    public function setResult(array $result)
    {
        $this->result = $result;
    }

    public function create()
    {
        $this->addNote('End', 1);

        foreach ($this->result as $title => $lines) {
            $this->addNote($title, 2);
            $this->addNote(implode('<br />', $lines));
        }

        if ($this->success) {
            $this->addNote(
                '<span style="font-weight:bold;">The installation has successfully completed!</span>' .
                ' You can now start exploring icingaweb by clicking "Finish". (Remember the default' .
                ' admin user you have defined earlier!)'
            );
            $this->setSubmitLabel('Finish');
        } else {
            $this->addNote(
                '<span style="font-weight:bold;">The installation seems to have failed!</span>' .
                ' Please fix the issues and retry the installation.'
            );
            $this->setSubmitLabel('Retry installation');
        }
    }

    /**
     * Inform the user that the installation has failed
     */
    public function retryInstallation()
    {
        $this->success = false;
        $this->stayOnPage();
    }

    /**
     * Inform the user that the installation was successful
     */
    public function finishInstallation()
    {
        $this->success = true;
        $this->endWizard();
    }
}
