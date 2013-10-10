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
 * Wizard-Page that displays a report about required software and extensions
 */
class RequirementsForm extends WizardForm
{
    public function create()
    {
        $this->addNote('Requirements', 1);

        $this->addNote($this->getReport()->render());

        if ($this->getReport()->isOk()) {
            $this->addNote(
                '<span style="font-weight:bold;">All required software and packages available.</span>'
              . ' You can now start configuring your new Icinga 2 Web installation!'
            );
            $this->setSubmitLabel('Continue');
        } else {
            $this->addNote(
                '<span style="font-weight:bold;">Some mandatory requirements are not fulfilled!</span>'
              . ' Please check your environment and install the appropriate software and packages.'
            );
            $this->setSubmitLabel('Re-check');
        }
    }
}
