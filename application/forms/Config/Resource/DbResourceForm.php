<?php
/**
 * Created by PhpStorm.
 * User: mjentsch
 * Date: 19.05.14
 * Time: 17:13
 */

namespace Icinga\Form\Config\Resource;

use Exception;
use Icinga\Data\ResourceFactory;
use Icinga\Web\Form\Element\Number;
use Icinga\Web\Form;
use Zend_Config;

/**
 * Contains the properties needed to create a basic SQL-Database resource.
 */
class DbResourceForm extends Form {

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
            'select',
            'resource_db_db',
            array(
                'required'      => true,
                'label'         => t('Database Type'),
                'helptext'      => t('The type of SQL database you want to create.'),
                'value'         => $this->getResource()->get('db', 'mysql'),
                'multiOptions'  => array(
                    'mysql'         => 'MySQL',
                    'pgsql'         => 'PostgreSQL'
                    //'oracle'        => 'Oracle'
                )
            )
        );

        $this->addElement(
            'text',
            'resource_db_host',
            array (
                'required'  => true,
                'label'     => t('Host'),
                'helptext'  => t('The hostname of the database.'),
                'value'     => $this->getResource()->get('host', 'localhost')
            )
        );

        $this->addElement(
            new Number(
                array(
                    'name'      => 'resource_db_port',
                    'required'  => true,
                    'label'     => t('Port'),
                    'helptext'  => t('The port to use.'),
                    'value'     => $this->getResource()->get('port', 3306)
                )
            )
        );

        $this->addElement(
            'text',
            'resource_db_dbname',
            array(
                'required'  => true,
                'label'     => t('Database Name'),
                'helptext'  => t('The name of the database to use'),
                'value'     => $this->getResource()->get('dbname', '')
            )
        );

        $this->addElement(
            'text',
            'resource_db_username',
            array (
                'required'  => true,
                'label'     => t('Username'),
                'helptext'  => t('The user name to use for authentication.'),
                'value'     => $this->getResource()->get('username', '')
            )
        );

        $this->addElement(
            'password',
            'resource_db_password',
            array(
                'required'          => true,
                'renderPassword'    => true,
                'label'             => t('Password'),
                'helptext'          => t('The password to use for authentication'),
                'value'             => $this->getResource()->get('password', '')
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
        try {
            $config = $this->getConfig();
            /*
             * It should be possible to run icingaweb without the pgsql or mysql extension or Zend-Pdo-Classes,
             * in case they aren't actually used. When the user tries to create a resource that depends on an
             * uninstalled extension, an error should be displayed.
             */
            if ($config->db === 'mysql' && !ResourceFactory::mysqlAvailable()) {
                $this->addErrorMessage(
                    t('You need to install the php extension "mysql" and the ' .
                        'Zend_Pdo_Mysql classes to use  MySQL database resources.')
                );
                return false;
            }
            if ($config->db === 'pgsql' && !ResourceFactory::pgsqlAvailable()) {
                $this->addErrorMessage(
                    t('You need to install the php extension "pgsql" and the ' .
                        'Zend_Pdo_Pgsql classes to use  PostgreSQL database resources.')
                );
                return false;
            }

            $resource = ResourceFactory::createResource($config);
            $resource->getConnection()->getConnection();
        } catch (Exception $e) {
            $this->addErrorMessage(t('Connectivity validation failed, connection to the given resource not possible.'));
            return false;
        }
    }
} 