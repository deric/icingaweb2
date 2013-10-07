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
 * Wizard-Page that prompts the user for database configuration details
 */
class DbConfigForm extends WizardForm
{
    public function create()
    {
        $this->addNote('Database configuration', 1);

        $this->addNote('Primary database store to use', 2);

        $this->addElement(
            'text',
            'resource_name',
            array(
                'label'         => 'Resource name',
                'helptext'      => 'This is the name internally used by Icinga 2 Web to identify this database store.',
                'required'      => true,
                'allowEmpty'    => false,
                'value'         => 'icinga2web'
            )
        );

        $this->addElement(
            'select',
            'db_provider',
            array(
                'label'         => 'Database provider',
                'helptext'      => 'Specifies the type or vendor of this database.',
                'required'      => true,
                'allowEmpty'    => false,
                'multiOptions'  => $this->getAvailableProviders()
            )
        );

        $this->addElement(
            'text',
            'db_host',
            array(
                'label'         => 'Hostname',
                'helptext'      => 'The host of this database.',
                'required'      => true,
                'allowEmpty'    => false,
                'value'         => 'localhost'
            )
        );

        $this->addElement(
            'text',
            'db_port',
            array(
                'label'         => 'Port',
                'helptext'      => 'The port of this database. (Leave blank to use the default.)',
                'allowEmpty'    => true
            )
        );

        $this->addElement(
            'text',
            'db_name',
            array(
                'label'         => 'Database name',
                'helptext'      => 'The name of this database.',
                'required'      => true,
                'allowEmpty'    => false,
                'value'         => 'icinga2web'
            )
        );

        $this->addElement(
            'text',
            'db_username',
            array(
                'label'         => 'Username',
                'helptext'      => 'The username to use for authentication with this database.',
                'required'      => true,
                'allowEmpty'    => false,
                'value'         => 'icinga2web'
            )
        );

        $this->addElement(
            'text',
            'db_password',
            array(
                'label'         => 'Password',
                'helptext'      => 'The password to use for authentication with this database.',
                'required'      => true,
                'allowEmpty'    => false,
                'value'         => 'icinga'
            )
        );

        $this->setSubmitLabel('Continue');
    }

    /**
     * Return a list of available database providers
     *
     * @return  array
     */
    private function getAvailableProviders()
    {
        $reportInfo = $this->getReport()->toArray();
        $providers = array();

        if ($reportInfo['hasMysqlExtension'] && $reportInfo['hasMysqlAdapter']) {
            array_push($providers, 'mysql');
        }
        if ($reportInfo['hasPgsqlExtension'] && $reportInfo['hasPgsqlAdapter']) {
            array_push($providers, 'pgsql');
        }

        return $providers;
    }
}
