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

require_once realpath(__DIR__ . '/WizardForm.php');

/**
 * Wizard-Page that displays a report about required software and extensions
 */
class RequirementsForm extends WizardForm
{
    /**
     * The report that is shown to the user
     *
     * @var array
     */
    private $report;

    /**
     * Set the report that should be shown to the user
     *
     * @param   array   $report     The report to display
     */
    public function setReport(array $report)
    {
        $this->report = $report;
    }

    public function create()
    {
        $canAdvance = true;
        $tableContent = '';
        foreach ($this->report as $requirementInfo) {
            if ($requirementInfo['state'] === -1) {
                $canAdvance = false;
                $tableContent .= '<tr class="danger">';
            } elseif ($requirementInfo['state'] === 0) {
                $tableContent .= '<tr class="warning">';
            } else {
                $tableContent .= '<tr class="success">';
            }

            $helpText = '';
            if (isset($requirementInfo['help']) && $requirementInfo['help']) {
                $helpText = '<br /><span style="font-size:.8em">' . $requirementInfo['help'] . '</span>';
            }
            $tableContent .= '<td>' . $requirementInfo['description'] . $helpText . '</td>';
            $tableContent .= '<td>' . $requirementInfo['note'] . '</td>';
            $tableContent .= '</tr>';
        }

        $this->addNote(
            '<table class="table">'
          . '  <thead>'
          . '    <tr>'
          . '      <th>Requirement</th>'
          . '      <th>State</th>'
          . '    </tr>'
          . '  </thead>'
          . '  <tbody>'
          . $tableContent
          . '  </tbody>'
          . '</table>'
        );

        if ($canAdvance) {
            $this->addNote(
                '<span style="font-weight:bold;">All required software and packages available.</span>'
              . ' You can now start configuring your new Icinga 2 Web installation!'
            );
            $this->advance('Continue');
        } else {
            $this->addNote(
                '<span style="font-weight:bold;">Some mandatory requirements are not fulfilled!</span>'
              . ' Please check your environment and install the appropriate software and packages.'
            );
        }
    }
}
