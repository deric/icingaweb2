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
            'db_resource',
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
                'multiOptions'  => $this->getDatabaseProviders()
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
            'password',
            'db_password',
            array(
                'label'         => 'Password',
                'helptext'      => 'The password to use for authentication with this database.',
                'required'      => true,
                'allowEmpty'    => false
            )
        );
        $this->addElement(
            'password',
            'db_password2',
            array(
                'label'         => 'Password confirmation',
                'helptext'      => 'Please enter the password a second time to avoid mistakes.',
                'required'      => true,
                'allowEmpty'    => false
            )
        );

        $this->setSubmitLabel('Continue');
    }

    /**
     * Return the provided details
     *
     * @return  array
     */
    public function getDetails()
    {
        return array(
            'db_resource'   => $this->getValue('db_resource'),
            'db_provider'   => $this->getValue('db_provider'),
            'db_host'       => $this->getValue('db_host'),
            'db_port'       => $this->getValue('db_port'),
            'db_name'       => $this->getValue('db_name'),
            'db_username'   => $this->getValue('db_username'),
            'db_password'   => $this->getValue('db_password')
        );
    }
}
