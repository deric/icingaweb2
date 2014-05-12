<?php
// @codeCoverageIgnoreStart
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

namespace Icinga\Form\Config;

use Zend_Config;
use Exception;
use Icinga\Web\Form;
use Zend_Validate_Identical;

/**
 * Form class for setting the application wide logging configuration
 */
class AdministratorForm extends Form
{
    /**
     * Create this logging configuration form
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->setName('form_config_administrator');

        $config = $this->getConfiguration();
        if (($administratorConfig = $config->administrator) === null) {
            $administratorConfig = new Zend_Config(array());
        }
        $authMode = $administratorConfig->get('authenticationMode', 'internal');
        if ($authMode === 'external') {
            $this->addElement(
                'text',
                'external_administrator',
                array(
                    'required'  => true,
                    'label'     => t('Administrator user name.'),
                    'helptext'  => t('The name of the user that should get administrator privileges.'),
                    'value'     => $administratorConfig->get('external_administrator', $_SERVER['REMOTE_USER'])
                )
            );
        } else {
            // TODO: Decide how an LdapUserBackend should be handled.
            $this->addElement(
                'checkbox',
                'internal_administrator_select_type',
                array(
                    'required'      => true,
                    'label'         => t('Create a new user.'),
                    'helptext'      => t('Do you want to create a new user as administrator?'),
                    'value'         => 0
                )
            );

            // TODO: fetch list of available users from database.
            $users = array('jdoe' => 'jdoe', 'foo' => 'foo', 'bar' => 'bar');

            if ($administratorConfig->get('internal_administrator_select_type', 0) === 0 && !(empty($users))) {
                $this->addElement(
                    'select',
                    'internal_administrator_existing_username',
                    array(
                        'required'      => true,
                        'label'         => t('Administrator username'),
                        'helptext'      => t('Choose the IcingaWeb administrator.'),
                        'value'         => $administratorConfig->get('internal_administrator_existing', key($users)),
                        'multiOptions'  => $users
                    )
                );
            } else {
                if (empty($users)) {
                    $this->addErrorMessage(
                        t('No users available, you need to create a new one.')
                    );
                    $this->getElement('internal_administrator_select_type')->setValue(1);
                }

                // Create new user
                $this->addElement(
                    'text',
                    'internal_administrator_new_username',
                    array(
                        'required'      => true,
                        'label'         => t('Administrator username'),
                        'helptext'      => t('Create a new IcingaWeb administrator.'),
                        'value'         => $administratorConfig->get('internal_administrator_new', 0)
                    )
                );

                $this->addElement(
                    'password',
                    'internal_administrator_new_password',
                    array(
                        'required'  => true,
                        'label' => t('Password'),
                        'helptext' => t('Provide the password of the new administrator.'),
                        'value' => $administratorConfig->get('internal_administrator_new_password', ''),
                        'validators' => array(array('NotEmpty'))
                    )
                );

                $this->addElement(
                    'password',
                    'internal_administrator_new_confirm_password',
                    array(
                        'required'  => true,
                        'label' => t('Confirm Password'),
                        'helptext' => t('Confirm the password of the new administrator.'),
                        'value' => $administratorConfig->get('internal_administrator_new_confirm_password', ''),
                        'validators' => array(new Zend_Validate_Identical('internal_administrator_new_password'))
                    )
                );
            }
        }

        $this->addElement(
            'button',
            'btn_submit',
            array(
                'type'      => 'submit',
                'escape'    => false,
                'value'     => '1',
                'label'     => $this->getView()->icon('save.png', 'Save Changes')
                    . ' Save changes',
            )
        );
    }

    public function isValid($data) {
        foreach ($this->getElements() as $key => $element) {
            // Initialize all empty elements with their default values.
            if (!isset($data[$key])) {
                $data[$key] = $element->getValue();
            }
        }
        return parent::isValid($data);
    }

    /**
     * Return a Zend_Config object containing the state defined in this form
     *
     * @return  Zend_Config     The config defined in this form
     */
    public function getConfig()
    {
        $values = $this->getValues();
        $cfg = $this->getConfiguration()->toArray();

        if (!array_key_exists('administrator', $cfg)) {
            $cfg['administrator'] = array();
        }

        if ($values['internal_administrator_select_type']) {
            $cfg['administrator']['username'] = $values['internal_administrator_existing_username'];
        } else {
            $cfg['administrator']['username'] = $values['internal_administrator_new_username'];
        }
        return new Zend_Config($cfg);
    }
}
// @codeCoverageIgnoreEnd
