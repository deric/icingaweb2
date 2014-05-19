<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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

namespace Icinga\Form\Install;

use \Icinga\Form\Config\ResourceForm;

/**
 * Choose a resource for an authentication backend
 *
 * Contains only the resource types that can be used with an
 */
class AuthenticationResourceForm extends ResourceForm {

    protected function addTypeSelectionBox()
    {
        $this->addElement(
            'select',
            'resource_type',
            array(
                'required'      => true,
                'label'         => t('Backend Type'),
                'helptext'      => t('Choose the type of authentication backend you want to use.'),
                'value'         => $this->getRequest()->getParam('resource_type', $this->getResource()->type),
                'multiOptions'  => array(
                    'db'            => t('SQL Database'),
                    'ldap'          => t('AD/LDAP'),
                    'external'      => t('External')
                )
            )
        );
        $this->enableAutoSubmit(array('resource_type'));
    }

    public function create()
    {
        $this->addNameFields();
        $this->addTypeSelectionBox();

        switch ($this->getRequest()->getParam('resource_type', $this->getResource()->type)) {
            case 'db':
                $this->addDbForm();
                break;;
            case 'ldap':
                $this->addLdapForm();
                break;
        }
    }

} 