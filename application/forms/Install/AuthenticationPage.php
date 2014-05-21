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

use Icinga\Form\Config\Resource\ResourceBaseForm;
use Icinga\Form\Config\ResourceForm;
use Icinga\Protocol\Ldap\Connection;
use Zend_Form;
use Icinga\Form\Config\Resource\DbResourceForm;
use Icinga\Form\Config\Resource\LdapResourceForm;
use Zend_Config;
use Zend_Validate_Ip;
use Zend_Validate_Hostname;
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
     * @var ResourceBaseForm
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

        // TODO: Will only work when next was clicked at least once.
        switch ($this->getRequest()->getParam('authentication_mode', self::AUTHENTICATION_MODE_DATABASE)) {
            case self::AUTHENTICATION_MODE_DATABASE:
                $this->setResourceSubForm(new DbResourceForm());
                break;
            case self::AUTHENTICATION_MODE_LDAP:
                $this->addElement(
                    'text',
                    'ldap_hostname_discover',
                    array(
                        'required' => true,
                        'label'    => t('Discover AD/LDAP Server'),
                        'helptext' => t('Enter the hostname, IP or domain of the AD/LDAP server and press "Check".')
                    )
                );
                $hostname = $this->getRequest()->getParam('ldap_hostname_discover');

                // check hostname
                $ip = new Zend_Validate_Ip();
                $domain = new Zend_Validate_Hostname();
                if (isset($hostname) && $domain->isValid($hostname)) {
                    // Try to discover servers
                    $connections = Connection::discoverServerlistForDomain($hostname);
                    if (count($connections) > 0) {
                        array_unshift($connections, '');
                        $this->addHostSelectBox($connections, count($connections) - 1);
                    } else {
                        $this->addErrorMessage(t('No Servers found on this domain.'));
                    }
                } else if (isset($hostname) && $ip->isValid($hostname)) {
                    $this->addHostSelectBox(array($hostname), 1);
                    $this->addLdapResourceForm();
                } else {
                    $this->addCheckButton();
                }
                if ($this->getRequest()->getParam('ldap_hostname') !== NULL) {
                    $this->addLdapResourceForm();
                }
                break;
            case self::AUTHENTICATION_MODE_EXTERNAL:
                // TODO: external subform
                break;
        }
    }

    private function addLdapResourceForm()
    {
        $hostname = $this->getElement('ldap_hostname')->getValue();
        if ($hostname === '') {
            return;
        }

        $form = new LdapResourceForm();
        $form->setResource(new Zend_Config(array()));
        $form->buildForm();
        $form->setDefault('resource_ldap_hostname', $hostname);
        $form->getElement('resource_ldap_hostname')->setValue($hostname);
        $this->setResourceSubForm($form);

        // TODO: Get credentials form input.
        $this->discoverCapabilities(array(
            'hostname' => $hostname,
            'port'     => '636',
            'bind_dn'  => 'DC=int,DC=netways,DC=de',
            'root_dn'  => 'DC=int,DC=netways,DC=de',
            'bind_pw'  => 'passwort'
        ));
    }

    private function discoverCapabilities($config)
    {
        $conn = new Connection(new Zend_Config($config));
        var_dump($conn->getDefaultNamingContext());
        var_dump($conn->namingContexts());
    }

    private function addHostSelectBox($connections, $count)
    {
        $this->addElement(
            'select',
            'ldap_hostname',
            array(
                'required'     => true,
                'label'        => t('Available Hosts'),
                'helptext'     => sprintf(t('We have discovered %d AD or LDAP servers. Choose one from this list.'), $count),
                'multiOptions' => $connections
            )
        );
        $this->enableAutoSubmit(array('ldap_hostname'));
    }

    private function addCheckButton()
    {
        $this->addElement(
            'button',
            'btn_submit',
            array(
                'type'   => 'submit',
                'escape' => false,
                'value'  => '1',
                'label'  => $this->getView()->icon('refresh.png', 'Check')
                    . ' Check',
            )
        );
    }

    private function setResourceSubForm(ResourceBaseForm $form)
    {
        $form->setResource($this->getConfig()->get(
            'resource',
            new Zend_Config(array()))
        );
        $form->buildForm();
        $this->addSubForm($form, 'resource');
        $this->resourceForm = $form;
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
