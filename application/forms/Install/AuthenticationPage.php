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
use Icinga\Web\Wizard\Page;

use Zend_Form;
use Icinga\Form\Config\Resource\DbResourceForm;
use Icinga\Form\Config\Resource\LdapResourceForm;
use Zend_Config;
use Icinga\Web\Form;

/**
 * Form class for setting the application wide logging configuration
 */
class AuthenticationPage extends Page
{
    /**
     * Users are authenticated using a database
     */
    const AUTHENTICATION_MODE_DATABASE = 'database';

    /**
     * Users are authenticated using ldap
     */
    const AUTHENTICATION_MODE_LDAP = 'ldap';

    /**
     * Users are authenticated using an external authentication backend
     */
    const AUTHENTICATION_MODE_EXTERNAL = 'external';

    /**
     * The sub form used to configure the resource.
     *
     * @var Zend_Form
     */
    private $resourceForm = null;

    /**
     * The sub form used to configure the authentication backend.
     *
     * @var Zend_Form
     */
    private $backendForm = null;

    /**
     * The title of this wizard-page.
     *
     * @var string
     */
    protected $title = '';

    /**
     * Initialise this form.
     */
    public function init()
    {
        $this->setName('authentication');
        $this->title = t('Authentication');
    }

    /**
     * Create this administration form.
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->addElement(
            'select',
            'authentication_mode',
            array(
                'required'     => true,
                'label'        => t('Authentication Method'),
                'helptext'     => t('Select the method you want to use to authenticate users.'),
                'value'        => self::AUTHENTICATION_MODE_DATABASE,
                'multiOptions' => array(
                    self::AUTHENTICATION_MODE_DATABASE => t('Database'),
                    self::AUTHENTICATION_MODE_LDAP     => t('AD/LDAP'),
                    self::AUTHENTICATION_MODE_EXTERNAL => t('External')
                )
            )
        );
        $this->enableAutoSubmit(array('authentication_mode'));
        switch ($this->getRequest()->getParam('authentication_mode', self::AUTHENTICATION_MODE_DATABASE)) {
            case self::AUTHENTICATION_MODE_DATABASE:
                $this->setResourceSubForm(new DbResourceForm());
                break;
            case self::AUTHENTICATION_MODE_LDAP:
                $this->setResourceSubForm(new LdapResourceForm());
                break;
            case self::AUTHENTICATION_MODE_EXTERNAL:
                // TODO: external subform
                break;
        }

        /*
        $this->addElement(
            'text',
            'backend_name',
            array(
                'required'  => true,
                'label'     => t('Authentication Backend Name'),
                'helptext'  => t('Provide an unique name, used to identify the new authentication backend.'),
                'value'     => 'auth_backend'
            )
        );

        if ($this->getRequest()->getParam('resource_type', 'db') === 'ldap') {
            $this->addElement(
                'text',
                'backend_ldap_user_class',
                array(
                    'required'  => true,
                    'label'     => t('LDAP User Object Class'),
                    'helptext'  => t('The object class used for storing users on the ldap server'),
                    'value'     => 'inetOrgPerson'
                )
            );
            $this->addElement(
                'text',
                'backend_ldap_user_name_attribute',
                array(
                    'required'  => true,
                    'label'     => t('LDAP User Name Attribute'),
                    'helptext'  => t('The attribute name used for storing the user name on the ldap server'),
                    'value'     => 'uid'
                )
            );
        }
        switch ($this->getRequest()->getParam('authentication_mode', self::AUTHENTICATION_MODE_DATABASE)) {
            case self::AUTHENTICATION_MODE_DATABASE:

                break;
            case self::AUTHENTICATION_MODE_LDAP:
                break;

            case self::AUTHENTICATION_MODE_EXTERNAL:
                // TODO: Extended configuration to change the value of REMOTE_USER, to be able to
                // remove the current realm, when using Kerberos authentication.
                break;
        }

        /*
            $this->addErrorMessage(
                t(
                    'No users available in the given LDAP backend, installation not possible.'
                    . ' Change the used backend configuration in the page "configuration" or add at'
                    . ' least one user to your ldap backend.'
                )
            );
        */
    }

    private function setBackendSubForm(Zend_Form $form)
    {
        // set resource to name of current resource form.

    }

    private function setResourceSubForm(Zend_Form $form)
    {
        $form->setResource(
            $this->getConfig()->get('resource', new Zend_Config(array()))
        );
        $this->resourceForm = $form;
        $this->addSubForm($form, 'resource');
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
        $config = array();
        if (isset($this->resourceForm)) {
            $config['resource'] = $this->resourceForm->getConfig();
        }
        if (isset($this->backendForm)) {
            $config['backend'] = $this->backendForm->getConfig();
        }
        /*
        if ($this->getRequest()->getParam('resource_e', 'db') === 'ldap') {
            $authBackendConf = array(
                'backend'             => 'ldap',
                'target'              => 'user',
                'resource'            => $this->authenticationResourceForm->getName(),
                'user_class'          => $values['backend_ldap_user_class'],
                'user_name_attribute' => $values['backend_ldap_user_name_attribute']
            );
        } else if ($this->getRequest()->getParam('resource_type', 'db') === 'db') {
            $authBackendConf = array(
                'backend'             => 'db',
                'target'              => 'user',
                'resource'            => $this->authenticationResourceForm->getName(),
                'user_class'          => $values['backend_ldap_user_class'],
                'user_name_attribute' => $values['backend_ldap_user_name_attribute']
            );
        } else {

        }I
        */
        return new Zend_Config($config);
    }
}
// @codeCoverageIgnoreEnd
