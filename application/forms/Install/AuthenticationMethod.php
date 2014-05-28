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

use Icinga\Form\Config\Authentication\LdapBackendForm;
use Icinga\Form\Config\Resource\ResourceBaseForm;
use Icinga\Form\Config\Authentication\BaseBackendForm;
use Icinga\Protocol\Ldap\Connection;
use Icinga\Form\Config\Resource\LdapResourceForm;
use Icinga\Web\Wizard\Page;
use Icinga\Web\Form;

use Zend_Form;
use Zend_Config;
use Zend_Form_Decorator_Abstract;
use Zend_Form_Element_Text;

/**
 * Form class for setting the application wide logging configuration
 */
class AuthenticationMethod extends Page
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
     * @return null|string
     */
    public function getName()
    {
        return 'authentication_method';
    }

    /**
     * Initialise this form.
     */
    public function init()
    {
        $this->setName('authentication_method');
        $this->title = t('Setup User Authentication');
    }

    /**
     * Create this administration form.
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->addNote(
            t(
                'IcingaWeb can use several different methods to authenticate users, using either a SQL Database, ' .
                    ' a LDAP/Active Directory server or by delegating the the authentication to the current web server.' .
                ' You need to setup at least one working authentication backend in order to be able to use IcingaWeb.'
            ),
            null
        );

        $this->addElement(
            'text',
            'resource_name',
            array(
                'required' => true,
                'label'    => t('Resource Name'),
                'value'    => 'authentication_resource',
                'helptext' => t('Resources provide data to IcingaWeb. '
                    . ' After the setup, you will be able to use this name to refer to the resource that is created by '
                    . ' completing this wizard page. Choose any name you like, or just leave the default one.'
                )
            )
        );

        $this->addElement(
            'select',
            'authentication_mode',
            array(
                'required'     => true,
                'label'        => t('Authentication Method'),
                'helptext'     => t(
                    'Please choose your preferred authentication method.'),
                'value'        => '',
                'multiOptions' => array(
                    '' => '',
                    self::AUTHENTICATION_MODE_DATABASE => t('Database'),
                    self::AUTHENTICATION_MODE_LDAP     => t('AD/LDAP'),
                    self::AUTHENTICATION_MODE_EXTERNAL => t('External')
                )
            )
        );
        $this->enableAutoSubmit(array('authentication_mode'));

        // TODO: Will only work when next was clicked at least once.
        switch ($this->getRequest()->getParam('authentication_mode', '')) {

            case self::AUTHENTICATION_MODE_DATABASE:
                $this->addNote('SQL-Database', 2);
                $this->addNote('You have chosen to use a database for user authentication. '
                    . ' You will be able to specify vendor and connection credentials in the next wizard steps.',
                    null
                );
                break;

            case self::AUTHENTICATION_MODE_LDAP:
                $this->addNote('Active Directory / LDAP', 2);
                $this->addNote('You have chosen to use Active Directory or LDAP for authentication. ' .
                    ' The next wizard steps will guide you through the process of discovering local directory servers ' .
                    ' and setting up search queries for available users.',
                    null
                );
                break;

            case self::AUTHENTICATION_MODE_EXTERNAL:
                $this->addNote('External Authentication', 2);
                $this->addNote('You have chosen to use external authentication. This mode will delegate the ' .
                    ' authentication method to the current web server.',
                    null
                );
                // TODO: external subform
                break;
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
        if (!parent::isValid($data)) {
            return false;
        }
     }

    /**
     * Return a Zend_Config object containing the state defined in this form
     *
     * @return  Zend_Config     The config defined in this form
     */
    public function getConfig()
    {
        $config = $this->getValues();
        var_dump($config);
        return new Zend_Config($config);
    }
}
// @codeCoverageIgnoreEnd
