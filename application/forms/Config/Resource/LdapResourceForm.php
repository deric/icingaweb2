<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}


namespace Icinga\Form\Config\Resource;

use Icinga\Web\Form;
use Zend_Config;

/**
 * Contains the properties needed to create a basic LDAP resource.
 */
class LdapResourceForm extends ResourceBaseForm {

    public function create() {
        $this->addElement(
            'text',
            'resource_ldap_hostname',
            array(
                'required'      => true,
                'allowEmpty'    => false,
                'label'         => t('Host'),
                'helptext'      => t('The hostname or address of the LDAP server to use for authentication'),
                'value'         => $this->getResource()->get('hostname', 'localhost')
            )
        );

        $this->addElement(
            'text',
            'resource_ldap_root_dn',
            array(
                'required'  => true,
                'label'     => t('Root DN'),
                'helptext'  => t('The path where users can be found on the ldap server'),
                'value'     => $this->getResource()->get('root_dn', 'ou=people,dc=icinga,dc=org')
            )
        );

        $this->addElement(
            'text',
            'resource_ldap_bind_dn',
            array(
                'required'  => true,
                'label'     => t('Bind DN'),
                'helptext'  => t('The user dn to use for querying the ldap server'),
                'value'     => $this->getResource()->get('bind_dn', 'cn=admin,cn=config')
            )
        );

        $this->addElement(
            'password',
            'resource_ldap_bind_pw',
            array(
                'required'          => true,
                'renderPassword'    => true,
                'label'             => t('Bind Password'),
                'helptext'          => t('The password to use for querying the ldap server'),
                'value'             => $this->getResource()->get('bind_pw', '')
            )
        );
    }

    public function getConfig()
    {
        $values = $this->getValues();
        return new Zend_Config(array(
            'type'     => 'ldap',
            'hostname' => $values['resource_ldap_hostname'],
            'root_dn'  => $values['resource_ldap_root_dn'],
            'bind_dn'  => $values['resource_ldap_bind_dn'],
            'bind_pw'  => $values['resource_ldap_bind_pw']
         ));
    }
} 