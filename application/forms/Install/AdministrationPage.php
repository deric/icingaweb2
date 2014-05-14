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

namespace Icinga\Form\Install;

use Icinga\Web\Wizard\Page;
use Zend_Config;
use Icinga\Web\Form;
use Zend_Validate_Identical;

/**
 * Form class for setting the application wide logging configuration
 */
class AdministrationPage extends Page
{
    /**
     * Administration account is authenticated using a database
     *
     * Available users can be listed and a new user can be created.
     */
    const AUTHENTICATION_MODE_DATABASE = 'database';

    /**
     * Administration account is authenticated using ldap
     *
     * All available users can be listed, a new user cannot be created.
     */
    const AUTHENTICATION_MODE_LDAP     = 'ldap';

    /**
     * Administration account is authenticated using an external authentication backend
     *
     * Available users can not be listed and users cannot be created.
     */
    const AUTHENTICATION_MODE_EXTERNAL = 'external';

    /**
     * Defines where the administrative user is authenticated and whether a
     * new user can be created.
     *
     * @var string
     */
    private $authenticationMode = self::AUTHENTICATION_MODE_DATABASE;

    /**
     * The title of this wizard-page.
     *
     * @var string
     */
    protected $title = '';

    /**
     * Contains all available users.
     *
     * @var array
     */
    private $users = array();

    /**
     * Initialise this form.
     */
    public function init()
    {
        $this->setName('administration');
        $this->title = t('Administration');
    }

    /**
     * Create this administration form.
     *
     * @see Form::create()
     */
    public function create()
    {
        $config = $this->getConfig();

        if (empty($users) && $this->authenticationMode === self::AUTHENTICATION_MODE_LDAP) {
            $this->addErrorMessage(
                t(
                    'No users available in the given LDAP backend, installation not possible.'
                    . ' Create a new user .'
                )
            );
            return;
        }
        if ($this->authenticationMode === 'external') {
            $this->addElement(
                'text',
                'external_administrator',
                array(
                    'required'  => true,
                    'label'     => t('Administrator user name.'),
                    'helptext'  => t('Give administration privileges to this user.'),
                    'value'     => $config->get('external_administrator', $_SERVER['REMOTE_USER'])
                )
            );
        } else {
            $this->addElement(
                'checkbox',
                'internal_administrator_select_type',
                array(
                    'required'  => true,
                    'disableHidden' => true,
                    'label' => t('Create User?'),
                    'helptext' => t('Do you want to give administration privileges to a new user or an existing one?')
                )
            );
            $this->enableAutoSubmit(array('internal_administrator_select_type'));

            if ($this->getRequest()->getParam('internal_administrator_select_type', 0) === 0  && !(empty($this->users))) {
                $this->addElement(
                    'select',
                    'internal_administrator_existing_username',
                    array(
                        'required'     => true,
                        'label'        => t('Username'),
                        'helptext'     => t('Give administration privileges to the selected user.'),
                        'value'        => $config->get('internal_administrator_existing', key($this->users)),
                        'multiOptions' => $this->users
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
                        'required'  => true,
                        'label'     => t('Username'),
                        'helptext'  => t('Create a new IcingaWeb administrator.'),
                        'value'     => $config->get('internal_administrator_new', '')
                    )
                );

                $this->addElement(
                    'password',
                    'internal_administrator_new_password',
                    array(
                        'required'   => true,
                        'label'      => t('Password'),
                        'helptext'   => t('Provide the password of the new administrator.'),
                        'value'      => $config->get('internal_administrator_new_password', ''),
                        'validators' => array(array('NotEmpty'))
                    )
                );

                $this->addElement(
                    'password',
                    'internal_administrator_new_confirm_password',
                    array(
                        'required'   => true,
                        'label'      => t('Confirm Password'),
                        'helptext'   => t('Confirm the password of the new administrator.'),
                        'value'      => $config->get('internal_administrator_new_confirm_password', ''),
                        'validators' => array(new Zend_Validate_Identical('internal_administrator_new_password'))
                    )
                );
            }
        }
    }

    /**
     * Return if the given set of data is valid.
     *
     * @param array $data   The form data.
     *
     * @return bool If the data is valid.
     */
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
        $cfg = array();
        if ($this->authenticationMode === 'external') {
            if (array_key_exists('external_administrator', $values)) {
                $cfg['external_administrator'] = $values['external_administrator'];
            }
        } else {
            if (
                array_key_exists('internal_administrator_select_type', $values)
                && $values['internal_administrator_select_type']
            ) {
                if (array_key_exists('internal_administrator_existing_username', $values)) {
                    $cfg['username'] = $values['internal_administrator_existing_username'];
                }
            } else {
                if (array_key_exists('internal_administrator_new_username', $values)) {
                    $cfg['username'] = $values['internal_administrator_new_username'];
                }
            }
        }
        return new Zend_Config($cfg);
    }
}
// @codeCoverageIgnoreEnd
