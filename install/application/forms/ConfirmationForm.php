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
 * Wizard-page that requires the user to confirm the installation
 */
class ConfirmationForm extends WizardForm
{
    public function create()
    {
        $session = $this->getSession();
        $resources = $this->getResources();
        $dbProviders = $this->getDatabaseProviders();
        $preferenceStores = $this->getPreferenceStores();

        $this->addNote('Overview / Confirmation', 1);

        $this->addNote('Database configuration', 2);
        $this->addNote(
            implode(
                '<br />',
                array(
                    'Resource name: ' . $session->databaseDetails['db_resource'],
                    'Database provider: ' . $dbProviders[$session->databaseDetails['db_provider']],
                    'Hostname: ' . $session->databaseDetails['db_host'],
                    'Port: ' . (empty($session->databaseDetails['db_port']) ? 'Default port' :
                                $session->databaseDetails['db_port']),
                    'Database name: ' . $session->databaseDetails['db_name'],
                    'Username: ' . $session->databaseDetails['db_username'],
                    'Password: ' . preg_replace('#.#', '*', $session->databaseDetails['db_password'])
                )
            )
        );

        $this->addNote('Authentication & Preferences', 2);
        $this->addNote('Default admin user', 3);
        $this->addNote(
            implode(
                '<br />',
                array(
                    'Username: ' . $session->authenticationDetails['auth_username'],
                    'Password: ' . preg_replace('#.#', '*', $session->authenticationDetails['auth_password'])
                )
            )
        );
        $this->addNote('Preferences configuration', 3);
        $this->addNote(
            'Preference store to use: ' . $preferenceStores[$session->authenticationDetails['auth_preference_store']]
        );

        if ($session->authenticationDetails['auth_use_ldap']) {
            $this->addNote('LDAP configuration', 3);

            $this->addNote(
                implode(
                    '<br />',
                    array(
                        'Hostname: ' . $session->authenticationDetails['auth_ldap_hostname'],
                        'root_dn: ' . $session->authenticationDetails['auth_ldap_root_dn'],
                        'bind_dn: ' . $session->authenticationDetails['auth_ldap_bind_dn'],
                        'bind_pw: ' . $session->authenticationDetails['auth_ldap_bind_pw'],
                        'user_class: ' . $session->authenticationDetails['auth_ldap_user_class'],
                        'user_name_attributes: ' . $session->authenticationDetails['auth_ldap_user_name_attributes']
                    )
                )
            );
        }

        $this->addNote('Backend configuration', 2);
        $this->addNote('IDO - Icinga Data Out', 3);
        $this->addNote(
            implode(
                '<br />',
                array(
                    'Backend name: ' . $session->backendDetails['backend_name'],
                    'Resource to use: ' . (in_array(intval($session->backendDetails['backend_selection']), $resources) ?
                                           $resources[$session->backendDetails['backend_selection']] :
                                           'Other existing database')
                )
            )
        );

        if (!in_array(intval($session->backendDetails['backend_selection']), $resources)) {
            $this->addNote('Database store', 4);

            $this->addNote(
                implode(
                    '<br />',
                    array(
                        'Resource name: ' . $session->backendDetails['backend_resource'],
                        'Database provider: ' . $dbProviders[$session->backendDetails['backend_provider']],
                        'Hostname: ' . $session->backendDetails['backend_host'],
                        'Port: ' . (empty($session->backendDetails['backend_port']) ? 'Default port' :
                                    $session->backendDetails['backend_port']),
                        'Database name: ' . $session->backendDetails['backend_dbname'],
                        'Username: ' . $session->backendDetails['backend_dbuser'],
                        'Password: ' . preg_replace('#.#', '*', $session->backendDetails['backend_dbpass'])
                    )
                )
            );
        }

        if ($session->backendDetails['backend_use_statusdat'] || $session->backendDetails['backend_use_livestatus']) {
            $this->addNote('Additional backends', 3);

            if ($session->backendDetails['backend_use_statusdat']) {
                $this->addNote('Status.dat', 4);

                $this->addNote(
                    implode(
                        '<br />',
                        array(
                            'Backend name: ' . $session->backendDetails['backend_statusdat_name'],
                            'Status file: ' . $session->backendDetails['backend_statusdat_file'],
                            'Objects file: ' . $session->backendDetails['backend_statusdat_objects']
                        )
                    )
                );
            }

            if ($session->backendDetails['backend_use_livestatus']) {
                $this->addNote('Livestatus', 4);

                $this->addNote(
                    implode(
                        '<br />',
                        array(
                            'Backend name: ' . $session->backendDetails['backend_livestatus_name'],
                            'Livestatus socket: ' . $session->backendDetails['backend_livestatus_socket']
                        )
                    )
                );
            }
        }

        $this->setSubmitLabel('Start installation');
    }
}
