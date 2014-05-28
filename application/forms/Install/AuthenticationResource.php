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

use Icinga\Form\Config\Authentication\LdapBackendForm;
use Icinga\Form\Config\Resource\ResourceBaseForm;
use Icinga\Form\Config\Authentication\BaseBackendForm;
use Icinga\Protocol\Ldap\Connection;
use Icinga\Form\Config\Resource\LdapResourceForm;
use Zend_Config;
use Zend_Validate_Ip;
use Zend_Validate_Hostname;
use Icinga\Web\Form;
use Zend_Form;
use Zend_Form_Decorator_Abstract;
use Zend_Form_Element_Text;

/**
 * Form class for setting the application wide logging configuration
 */
class AuthenticationResource extends Page
{

    /**
     * The sub form used to configure the resource.
     *
     * @var ResourceBaseForm
     */
    private $resourceForm = null;

    /**
     * The sub form used to configure the authentication backend.
     *
     * @var BaseBackendForm
     */
    private $authForm = null;

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
    protected $title = 'authentication_resource';

    /**
     * Initialise this form.
     */
    public function init()
    {
        $this->setName('authentication_resource');
        $this->title = t('Authentication');
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return 'authentication_resource';
    }

    /**
     * Create this administration form.
     *
     * @see Form::create()
     */
    public function create()
    {
        // TODO: Will only work when next was clicked at least once.
        switch ($this->getRequest()->getParam('authentication_mode', '')) {
            case AuthenticationMethod::AUTHENTICATION_MODE_DATABASE:
                //$this->setResourceSubForm(new DbResourceForm());
                break;

            case AuthenticationMethod::AUTHENTICATION_MODE_LDAP:
                $discover = $this->createDiscoverElement();
                $this->addElement($discover);
                $hostname = $this->getRequest()->getParam('ldap_hostname_discover');

                // check hostname
                $ip     = new Zend_Validate_Ip();
                $domain = new Zend_Validate_Hostname();
                if (isset($hostname) && ($domain->isValid($hostname) || $hostname === 'localhost')) {
                    // Try to discover servers
                    $connections = Connection::discoverServerlistForDomain($hostname);
                    if (count($connections) > 0) {
                        $discover->setAttrib('helptext',
                            sprintf(
                                t('Discovery successful, found %s servers: %s'),
                                count($connections),
                                implode(', ', $connections)
                            )
                        );
                        $this->addLdapResourceForm($hostname, $this->getRequest()->getParam('resource_name'));
                    } else {
                        $msg = 'Discovery failed, no ldap servers found. If your directory server does not support '
                            . ' discovery, you probably need to specify the connection data manually.';
                        $discover->setAttrib('helptext', t($msg));
                        $this->addErrorMessage(t($msg));
                    }
                } else if (isset($hostname) && $ip->isValid($hostname)) {
                    $msg = 'Cannot perform dns lookup on IP address, please specify the connection data manually.';
                    $this->addLdapResourceForm($hostname, $this->getRequest()->getParam('resource_name'));
                    $discover->setAttrib('helptext', t($msg));
                } else {
                    $this->addErrorMessage('Not a valid IP, hostname or Domain.');
                }
                break;

            case self::AUTHENTICATION_MODE_EXTERNAL:
                // TODO: external subform
                break;
        }
    }

    private function createDiscoverElement()
    {
        $discover = new Zend_Form_Element_Text(
            'ldap_hostname_discover',
            array(
                'required' => true,
                'label'    => t('Discover AD/LDAP Server'),
                'helptext' => t(
                    'Enter the hostname, IP or domain of the AD/LDAP server and press "Discover". IcingaWeb will '
                    . ' search for existing LDAP or ActiveDirectory servers, discover their capabilities and '
                    . ' fill out some form elements for you.'
                )
            )
        );
        $discover->setDecorators(
            array(
                'ViewHelper',
                array('Description', array('escape' => false, 'tag' => false)),
                array(
                    'HtmlTag',
                    array('tag' => 'dd')
                ),
                // Discover-Button
                array('callback', array(
                    'callback'  => function() {
                            return '<div style="display: block;" class="form-group" id="btn_submit-element">'
                              . '<button name="btn_submit" id="btn_submit" type="submit" value="1">'
                                  . '<img src="/icingaweb/img/icons/refresh.png" class="icon" title="Discover" alt="">'
                              . ' Discover</button></div>';
                    },
                    'option'    => 'value',
                    'placement' => Zend_Form_Decorator_Abstract::APPEND
                )),
                array('Label', array('tag' => 'dt')),
                'Errors',
            )
        );
        return $discover;
    }

    private function addLdapResourceForm($hostname, $resourceName)
    {
        $form = new LdapResourceForm();
        $form->setResource(new Zend_Config(array()));
        $form->buildForm();
        $form->setDefault('resource_ldap_hostname', $hostname);
        $form->getElement('resource_ldap_hostname')->setValue($hostname);
        $this->setResourceSubForm($form);
        $this->addLdapBackendForm($hostname, $form);
    }

    private function addLdapBackendForm($hostname, $resourceForm)
    {
        $name = 'ldap_authentication';

        $form = new LdapBackendForm();
        $form->showButton = false;
        $form->setBackendName($name);
        $config = $this->getConfig()->get('backend', new Zend_Config(array()));
        $form->setBackend($config);
        $form->buildForm();

        $form->getElement('backend_' . $name . '_resource')
            ->setValue($this->getElement('resource_name')->getValue());

        $form->removeElement('backend_' . $name . '_resource');

        // TODO: Get credentials form input.
        $cap = $this->discoverCapabilities($hostname);
        if ($cap->msCapabilities->ActiveDirectoryOid) {
            // Host is an ActiveDirectory server
            if (isset($cap->defaultNamingContext)) {
                $resourceForm->setDefault('resource_ldap_root_dn', $cap->defaultNamingContext);
                $resourceForm->getElement('resource_ldap_root_dn')->setValue($cap->defaultNamingContext);
            }
            $form->setDefault('backend_' . $name . '_user_name_attribute', 'sAMAccountName');
            $form->getElement('backend_' . $name . '_user_name_attribute')->setValue('sAMAccountName');
            $form->setDefault('backend_' . $name . '_user_class', 'user');
            $form->getElement('backend_' . $name . '_user_class')->setValue('user');
        }
        $this->setAuthSubForm($form);
    }

    private function discoverCapabilities($hostname)
    {
        $conn = new Connection(
            new Zend_Config(array('hostname' => $hostname))
        );
        $conn->connect();
        return $conn->getCapabilities();
    }

    private function setResourceSubForm(ResourceBaseForm $form)
    {
        $config = $this->getConfig()->get('resource', new Zend_Config(array()));
        $form->setResource($config);
        $form->buildForm();
        $this->addSubForm($form, 'resource');
        $this->resourceForm = $form;
    }

    private function setAuthSubForm(BaseBackendForm $form)
    {
        $this->addSubForm($form, 'backend');
        return $form;
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
        if (!parent::isValid($data)) {
            return false;
        }
        if (!$this->resourceForm->isValidResource()) {
            $this->addErrorMessages($this->resourceForm->getErrorMessages());
            return false;
        }
        if ($this->authForm->isValidAuthenticationBackend()) {
            $this->addErrorMessages($this->authForm->getErrorMessages());
            return false;
        }
         switch ($this->getRequest()->getParam('authentication_mode')) {
            case self::AUTHENTICATION_MODE_DATABASE:
                // TODO: Check if database and test if it is writable. If no database exists skip.
                break;
            case self::AUTHENTICATION_MODE_LDAP:
                // TODO: Check if there are any users in the ldap backend, if there arent any, skip.
                break;
        }
        return true;
     }

    /**
     * Return a Zend_Config object containing the state defined in this form
     *
     * @return  Zend_Config     The config defined in this form
     */
    public function getConfig()
    {
        $config = $this->getValues();
        if (isset($this->resourceForm)) {
            $config['resource'] = $this->resourceForm->getConfig();
        }
        if (isset($this->backendForm)) {
            $config['backend'] = $this->backendForm->getConfig();
        }
        return new Zend_Config($config);
    }
}
// @codeCoverageIgnoreEnd
