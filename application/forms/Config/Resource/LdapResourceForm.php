<?php
/**
 * Created by PhpStorm.
 * User: mjentsch
 * Date: 19.05.14
 * Time: 17:22
 */

namespace Icinga\Form\Config\Resource;

use Exception;
use Icinga\Web\Form;
use Zend_Config;

/**
 * Contains the properties needed to create a basic LDAP resource.
 */
class LdapResourceForm extends Form {

    private $resource = null;

    /**
     * Set the resource configuration to edit.
     *
     * @param   Zend_Config     $resource   The config to set
     */
    public function setResource(Zend_Config $resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getConfig()
    {
        $values = $this->getValues();
        $result = array();
        foreach ($values as $key => $value) {
            $configKey = explode('_', $key, 3);
            if (count($configKey) === 3) {
                $result[$configKey[2]] = $value;
            }
        }
        return new Zend_Config($result);
    }

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

    /**
     * Test if this is a valid resource.
     *
     * @return bool
     */
    public function isValidResource()
    {
        $config = $this->getConfig();
        try {
            $resource = ResourceFactory::createResource($config);
            $resource->connect();
        } catch (Exception $e) {
            $this->addErrorMessage(t('Connectivity validation failed, connection to the given resource not possible.'));
            return false;
        }
        return true;
    }
} 