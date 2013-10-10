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
use \Icinga\Installer\Validators\PasswordValidator;

/**
 * Wizard-page that prompts the user to configure the authentication
 */
class AuthConfigForm extends WizardForm
{
    public function create()
    {
        $this->addNote('Authentication & Preferences', 1);

        $this->addNote(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut' .
            ' labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores' .
            ' et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem' .
            ' ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et' .
            ' dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.' .
            ' Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'
        );

        $this->addNote('Default admin user', 2);

        $this->addElement(
            'text',
            'auth_username',
            array(
                'label'         => 'Username',
                'helptext'      => 'The name of the default admin user.',
                'required'      => true,
                'allowEmpty'    => false,
                'value'         => 'icingaadmin'
            )
        );
        $this->addElement(
            'password',
            'auth_password',
            array(
                'label'         => 'Password',
                'helptext'      => 'The password of the default admin user.',
                'required'      => true,
                'allowEmpty'    => false
            )
        );
        $this->addElement(
            'password',
            'auth_password2',
            array(
                'label'         => 'Password confirmation',
                'helptext'      => 'Please enter the password a second time to avoid mistakes.',
                'required'      => true,
                'allowEmpty'    => false
            )
        );

        $this->addNote('Preferences configuration', 2);

        $this->addElement(
            'select',
            'auth_preference_store',
            array(
                'label'         => 'Preference store to use',
                'helptext'      => 'Please select how you want user preferences being stored.',
                'required'      => true,
                'allowEmpty'    => false,
                'multiOptions'  => $this->getPreferenceStores()
            )
        );

        $reportInfo = $this->getReport()->toArray();
        if ($reportInfo['hasLdapExtension']) {
            $this->addNote('LDAP configuration', 2);

            $this->addElement(
                'checkbox',
                'auth_use_ldap',
                array(
                    'label' => 'Use LDAP as primary authentication provider:',
                    'required' => true
                )
            );
            $this->enableAutoSubmit(array('auth_use_ldap'));

            if ($this->getRequest()->getPost('auth_use_ldap')) {
                $this->addElement(
                    'text',
                    'auth_ldap_hostname',
                    array(
                        'label'         => 'Hostname',
                        'required'      => true,
                        'allowEmpty'    => false
                    )
                );
                $this->addElement(
                    'text',
                    'auth_ldap_root_dn',
                    array(
                        'label'         => 'root_dn',
                        'required'      => true,
                        'allowEmpty'    => false
                    )
                );
                $this->addElement(
                    'text',
                    'auth_ldap_bind_dn',
                    array(
                        'label'         => 'bind_dn',
                        'required'      => true,
                        'allowEmpty'    => false
                    )
                );
                $this->addElement(
                    'text',
                    'auth_ldap_bind_pw',
                    array(
                        'label'         => 'bind_pw',
                        'required'      => true,
                        'allowEmpty'    => false
                    )
                );
                $this->addElement(
                    'text',
                    'auth_ldap_user_class',
                    array(
                        'label'         => 'user_class',
                        'required'      => true,
                        'allowEmpty'    => false
                    )
                );
                $this->addElement(
                    'text',
                    'auth_ldap_user_name_attributes',
                    array(
                        'label'         => 'user_name_attributes',
                        'required'      => true,
                        'allowEmpty'    => false
                    )
                );
            }
        }

        $passwordValidator = new PasswordValidator();
        $passwordValidator->setCounterpart('auth_password2');
        $this->getElement('auth_password')->addValidator($passwordValidator);

        $this->setSubmitLabel('Continue');
    }

    /**
     * Validate the form and check if the provided LDAP details are correct
     *
     * @param   array    $data      The submitted details
     * @return  bool                Whether the form and the details are valid
     */
    public function isValid($data)
    {
        $isValid = parent::isValid($data);

        if ($isValid && isset($data['auth_use_ldap']) && $data['auth_use_ldap']) {
            $message = $this->checkLdapAuthentication(
                new Zend_Config(
                    array(
                        'hostname'              => $data['auth_ldap_hostname'],
                        'root_dn'               => $data['auth_ldap_root_dn'],
                        'bind_dn'               => $data['auth_ldap_bind_dn'],
                        'bind_pw'               => $data['auth_ldap_bind_pw'],
                        'user_class'            => $data['auth_ldap_user_class'],
                        'user_name_attribute'   => $data['auth_ldap_user_name_attributes']
                    )
                )
            );
            $isValid = $message === 'OK';

            if (!$isValid) {
                $this->addErrorNote('Invalid LDAP authentication details: ' . $message, 10);
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
            'auth_username'                     => $this->getValue('auth_username'),
            'auth_password'                     => $this->getValue('auth_password'),
            'auth_preference_store'             => $this->getValue('auth_preference_store'),
            'auth_use_ldap'                     => $this->getValue('auth_use_ldap'),
            'auth_ldap_hostname'                => $this->getValue('auth_ldap_hostname'),
            'auth_ldap_root_dn'                 => $this->getValue('auth_ldap_root_dn'),
            'auth_ldap_bind_dn'                 => $this->getValue('auth_ldap_bind_dn'),
            'auth_ldap_bind_pw'                 => $this->getValue('auth_ldap_bind_pw'),
            'auth_ldap_user_class'              => $this->getValue('auth_ldap_user_class'),
            'auth_ldap_user_name_attributes'    => $this->getValue('auth_ldap_user_name_attributes')
        );
    }
}
