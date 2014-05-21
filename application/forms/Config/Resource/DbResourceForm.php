<?php
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

namespace Icinga\Form\Config\Resource;

use Exception;
use Icinga\Data\ResourceFactory;
use Icinga\Web\Form\Element\Number;
use Icinga\Web\Form;
use Zend_Config;

/**
 * Contains the properties needed to create a basic SQL-Database resource.
 */
class DbResourceForm extends ResourceBaseForm {

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

    public function getConfig()
    {
        $values = $this->getValues();
        return new Zend_Config(array(
            'type'      => 'db',
            'db'        => $values['resource_db_db'],
            'host'      => $values['resource_db_host'],
            'port'      => $values['resource_db_port'],
            'password'  => $values['resource_db_password'],
            'username'  => $values['resource_db_username'],
            'dbname'    => $values['resource_db_dbname']
        ));
    }

    /**
     * Test if this is a valid resource.
     *
     * @return bool
     */
    public function isValidResource($data)
    {
        try {
            $config = $this->createConfig($data);
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