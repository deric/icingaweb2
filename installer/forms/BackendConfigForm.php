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

use \Zend_Config;

/**
 * Wizard-page that prompts the user to configure the authentication
 */
class BackendConfigForm extends WizardForm
{
    public function create()
    {
        $this->addNote('Backend configuration', 1);

        $this->addNote(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut' .
            ' labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores' .
            ' et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem' .
            ' ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et' .
            ' dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.' .
            ' Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'
        );

        $this->addElement(
            'text',
            'backend_name',
            array(
                'label'         => 'Backend name',
                'helptext'      => 'This is the name internally used by icingaweb to identify this backend.',
                'required'      => true,
                'allowEmpty'    => false
            )
        );
        $this->addElement(
            'select',
            'backend_selection',
            array(
                'label'         => 'Type of backend to use',
                'required'      => true,
                'allowEmpty'    => false,
                'multiOptions'  => array(
                    'type_ido'  => 'IDO - Icinga Data Out',
                    'type_dat'  => 'Local file: status.dat',
                    'type_live' => 'Livestatus'
                )
            )
        );

        $this->addNote('Connection details', 2);

        $backendSelection = $this->getRequest()->getPost('backend_selection');
        if ($backendSelection === null || $backendSelection === 'type_ido') {
            $this->addElement(
                'select',
                'backend_ido_provider',
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
                'backend_ido_host',
                array(
                    'label'         => 'Hostname',
                    'helptext'      => 'The host of this database.',
                    'required'      => true,
                    'allowEmpty'    => false
                )
            );
            $this->addElement(
                'text',
                'backend_ido_port',
                array(
                    'label'         => 'Port',
                    'helptext'      => 'The port of this database. (Leave blank to use the default.)',
                    'allowEmpty'    => true,
                    'validators'    => array('int')
                )
            );
            $this->addElement(
                'text',
                'backend_ido_dbname',
                array(
                    'label'         => 'Database name',
                    'helptext'      => 'The name of this database.',
                    'required'      => true,
                    'allowEmpty'    => false
                )
            );
            $this->addElement(
                'text',
                'backend_ido_dbuser',
                array(
                    'label'         => 'Username',
                    'helptext'      => 'The username to use for authentication with this database.',
                    'required'      => true,
                    'allowEmpty'    => false
                )
            );
            $this->addElement(
                'password',
                'backend_ido_dbpass',
                array(
                    'label'         => 'Password',
                    'helptext'      => 'The password to use for authentication with this database.',
                    'required'      => true,
                    'allowEmpty'    => false
                )
            );
        } elseif ($backendSelection === 'type_dat') {
            $this->addElement(
                'text',
                'backend_dat_file',
                array(
                    'label'         => 'Status file',
                    'helptext'      => 'The path to the local status.dat file.',
                    'required'      => true,
                    'allowEmpty'    => false,
                    'value'         => '/usr/local/icinga/var/status.dat'
                )
            );
            $this->addElement(
                'text',
                'backend_dat_objects',
                array(
                    'label'         => 'Objects file',
                    'helptext'      => 'The path to the local objects.cache file.',
                    'required'      => true,
                    'allowEmpty'    => false,
                    'value'         => '/usr/local/icinga/var/objects.cache'
                )
            );
        } elseif ($backendSelection === 'type_live') {
            $this->addElement(
                'text',
                'backend_live_socket',
                array(
                    'label'         => 'Livestatus socket',
                    'helptext'      => 'The path to the local livestatus socket.',
                    'required'      => true,
                    'allowEmpty'    => false,
                    'value'         => '/var/lib/icinga/rw/live'
                )
            );
        }

        $this->enableAutoSubmit(array('backend_selection'));
        $this->setSubmitLabel('Continue');
    }

    /**
     * Validate the form and check if the provided backend details are correct
     *
     * @param   array    $data      The submitted details
     * @return  bool                Whether the form and the details are valid
     */
    public function isValid($data)
    {
        $isValid = parent::isValid($data);

        if ($isValid && $data['backend_selection'] === 'type_ido') {
            $message = $this->checkDatabaseConnection(
                new Zend_Config(
                    array(
                        'type'      => 'db',
                        'db'        => $data['backend_ido_provider'],
                        'dbname'    => $data['backend_ido_dbname'],
                        'host'      => $data['backend_ido_host'],
                        'port'      => $data['backend_ido_port'],
                        'username'  => $data['backend_ido_dbuser'],
                        'password'  => $data['backend_ido_dbpass']
                    )
                )
            );
            $isValid = $message === 'OK';

            if (!$isValid) {
                $this->addErrorNote('Database connection could not be established: ' . $message, 5);
            }
        } elseif ($isValid && $data['backend_selection'] === 'type_dat') {
            $message = $this->checkStatusDat(
                new Zend_Config(
                    array(
                        'status_file'   => $data['backend_dat_file'],
                        'objects_file'  => $data['backend_dat_objects']
                    )
                )
            );
            $isValid = $message === 'OK';

            if (!$isValid) {
                $this->addErrorNote('Invalid Status.dat backend: ' . $message, 5);
            }
        } elseif ($isValid && $data['backend_selection'] === 'type_live') {
            $message = $this->checkLiveStatus($data['backend_live_socket']);
            $isValid = $message === 'OK';

            if (!$isValid) {
                $this->addErrorNote('Invalid Livestatus backend: ' . $message, 5);
            }
        }

        return $isValid;
    }

    /**
     * Return the provided details
     *
     * @return  array
     */
    public function getDetails()
    {
        return array(
            'backend_name'          => $this->getValue('backend_name'),
            'backend_ido_provider'  => $this->getValue('backend_ido_provider'),
            'backend_ido_host'      => $this->getValue('backend_ido_host'),
            'backend_ido_port'      => $this->getValue('backend_ido_port'),
            'backend_ido_dbname'    => $this->getValue('backend_ido_dbname'),
            'backend_ido_dbuser'    => $this->getValue('backend_ido_dbuser'),
            'backend_ido_dbpass'    => $this->getValue('backend_ido_dbpass'),
            'backend_dat_file'      => $this->getValue('backend_dat_file'),
            'backend_dat_objects'   => $this->getValue('backend_dat_objects'),
            'backend_live_socket'   => $this->getValue('backend_live_socket')
        );
    }
}
