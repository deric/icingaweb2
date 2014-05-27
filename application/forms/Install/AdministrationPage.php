<?php
// @codeCoverageIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Install;

use Zend_Config;
use Zend_Validate_Identical;
use Icinga\Web\Wizard\Page;
use Icinga\Data\ResourceFactory;
use Icinga\Authentication\Backend\DbUserBackend;
use Icinga\Authentication\Backend\LdapUserBackend;

class AdministrationPage extends Page
{
    /**
     * Create this wizard page
     */
    public function create()
    {
        $config = $this->getConfiguration();
        $authConfig = $this->wizard->getPage('authentication')->getConfiguration();
        // TODO: Use auth configuration from the authentication page (refs #6141)
        $users = array(); // $this->getUsersFromBackend($authConfig);

        $this->addNote(t('Please define a user which should be initially equipped with administrative rights.'));

        if ($authConfig->type === 'external') {
            $this->addExternalUserControls($users);
        } elseif ($authConfig->type === 'ldap') {
            $this->addExistingUserControls($users);
        } elseif (empty($users)) {
            $this->addNewUserControls();
        } else {
            $this->addElement(
                'checkbox',
                'use_existing',
                array(
                    'required'  => true,
                    'label'     => t('Use existing user:'),
                    'value'     => !$config->user_name ? 1 : array_search($config->user_name, $users) !== false,
                    'helptext'  => t('Please decide whether to use an existing user or to create a new one.')
                )
            );
            $this->enableAutoSubmit(array('use_existing'));

            if ($this->getRequest()->getParam('use_existing', $this->getElement('use_existing')->getValue())) {
                $this->addExistingUserControls($users);
            } else {
                $this->addNewUserControls();
            }
        }
    }

    /**
     * Return a Zend_Config object containing the state defined in this form
     *
     * @return  Zend_Config     The config defined in this form
     */
    public function getConfig()
    {
        $config = array();

        $values = $this->getValues();
        if (isset($values['external_user'])) {
            $config['user_name'] = $values['external_user'];
        } elseif (isset($values['existing_user'])) {
            $config['user_name'] = $values['existing_user'];
        } elseif (isset($values['new_user']) && isset($values['password1'])) {
            $config['user_name'] = $values['new_user'];
            $config['password'] = $values['password1'];
        }

        return new Zend_Config($config);
    }

    /**
     * Return all users from the configured backend
     *
     * @param   Zend_Config     $config     The backend configuration
     *
     * @return  array                       The found usernames
     */
    protected function getUsersFromBackend(Zend_Config $config)
    {
        $resource = ResourceFactory::createResource($config->resource_configuration);
        switch ($config->type) {
            case 'db':
                $authBackend = new DbUserBackend($resource);
                return $authBackend->getUsers();
            case 'ldap':
                $authBackend = new LdapUserBackend($resource, $config->user_class, $config->user_name_attribute);
                return $authBackend->getUsers();
            case 'external':
                // TODO: Fetch the currently logged in user from a external auth provider (refs #6081)
            default:
                return array();
        }
    }

    /**
     * Add input element to define an external username
     *
     * @param   array   $users  The users the user can select
     */
    protected function addExternalUserControls(array $users) {
        $config = $this->getConfiguration();
        $this->addElement(
            'text',
            'external_user',
            array(
                'required'  => true,
                'label'     => t('Username:'),
                'helptext'  => t('Put in here the name of the user which will authenticate externally.'),
                'value'     => !$config->user_name ? (empty($users) ? '' : $users[0]) : $config->user_name
            )
        );
    }

    /**
     * Add a select box with the given users as values
     *
     * @param   array   $users  The users the user can select
     */
    protected function addExistingUserControls(array $users)
    {
        $config = $this->getConfiguration();
        $this->addElement(
            'select',
            'existing_user',
            array(
                'required'      => true,
                'multiOptions'  => $users,
                'value'         => array_search($config->user_name, $users) || 0,
                'label'         => t('Username:'),
                'helptext'      => t('Please choose an existing user.')
            )
        );
    }

    /**
     * Add input elements to create a new user
     */
    protected function addNewUserControls()
    {
        $config = $this->getConfiguration();
        $this->addElement(
            'text',
            'new_user',
            array(
                'required'  => true,
                'label'     => t('Username:'),
                'helptext'  => t('The name of the user to create.'),
                'value'     => $config->get('new_user', '')
            )
        );
        $this->addElement(
            'password',
            'password1',
            array(
                'required'   => true,
                'label'      => t('Password:'),
                'helptext'   => t('Provide a password for the new user.'),
                'value'      => $config->get('password1', ''),
                'validators' => array(array('NotEmpty'))
            )
        );
        $this->addElement(
            'password',
            'password2',
            array(
                'required'   => true,
                'label'      => t('Confirm Password:'),
                'helptext'   => t('Please repeat the password you\'ve provided above to verify that it\'s correct.'),
                'value'      => $config->get('password2', ''),
                'validators' => array(new Zend_Validate_Identical('password1'))
            )
        );
    }
}
// @codeCoverageIgnoreEnd
