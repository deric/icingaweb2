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

namespace Icinga\Form\Config;

use Exception;
use Icinga\Form\Config\Resource\DbResourceForm;
use Icinga\Form\Config\Resource\LdapResourceForm;
use Zend_Config;
use Zend_Form_Element_Checkbox;
use Icinga\Web\Form;
use Icinga\Data\ResourceFactory;
use Icinga\Web\Form\Decorator\HelpText;

class ResourceForm extends Form
{
    /**
     * The resource
     *
     * @var Zend_Config
     */
    protected $resource;

    /**
     * The (new) name of the resource
     *
     * @var string
     */
    protected $name;

    /**
     * The old name of the resource
     *
     * @var string
     */
    protected $oldName;

    /**
     * The subForm for the currently used resource.
     *
     * @var Zend_Form
     */
    protected $resourceSubForm;

    /**
     * Set the current resource name
     *
     * @param   string      $name   The name to set
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the current resource name
     *
     * @return null|string
     */
    public function getName()
    {
        $name = $this->getValue('resource_all_name');
        if (!$name) {
            return $this->name;
        }

        return $name;
    }

    /**
     * Set the original name of the resource
     *
     * @param   string      $name   The name to set
     */
    public function setOldName($name)
    {
        $this->oldName = $name;
    }

    /**
     * Get the resource name that was initially set
     *
     * @return  null|string
     */
    public function getOldName()
    {
        $oldName = $this->getValue('resource_all_name_old');
        if (!$oldName) {
            return $this->oldName;
        }

        return $oldName;
    }

    /**
     * Set the resource configuration to edit.
     *
     * @param   Zend_Config     $resource   The config to set
     */
    public function setResource(Zend_Config $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Get the current resource configuration.
     *
     * @return  Zend_Config
     */
    public function getResource()
    {
        if (!isset($this->resource)) {
            $this->resource = new Zend_Config(array('type' => 'db'));
        }

        return $this->resource;
    }

    protected function addDbForm()
    {
        $dbResource = new DbResourceForm();
        $dbResource->setResource($this->getResource());
        $this->addSubForm($dbResource, 'db_resource');
    }

    protected function addStatusdatForm()
    {
        $this->addElement(
            'text',
            'resource_statusdat_status_file',
            array(
                'required'  => true,
                'label'     => t('Filepath'),
                'helptext'  => t('Location of your icinga status.dat file'),
                'value'     => $this->getResource()->get('status_file', '/usr/local/icinga/var/status.dat')
            )
        );

        $this->addElement(
            'text',
            'resource_statusdat_object_file',
            array(
                'required'  => true,
                'label'     => t('Filepath'),
                'helptext'  => t('Location of your icinga objects.cache file'),
                'value'     => $this->getResource()->get('status_file', '/usr/local/icinga/var/objects.cache')
            )
        );
    }

    protected function addLivestatusForm()
    {
        $this->addElement(
            'text',
            'resource_livestatus_socket',
            array(
                'required'  => true,
                'label'     => t('Socket'),
                'helptext'  => t('The path to your livestatus socket used for querying monitoring data'),
                'value'     => $this->getResource()->get('socket', '/usr/local/icinga/var/rw/livestatus')
            )
        );
    }

    protected function addLdapForm()
    {
        $ldapResource = new LdapResourceForm();
        $ldapResource->setResource($this->getResource());
        $this->addSubForm($ldapResource, 'ldap_resource');
    }

    protected function addFileForm()
    {
        $this->addElement(
            'text',
            'resource_file_filename',
            array(
                'required'  => true,
                'label'     => t('Filepath'),
                'helptext'  => t('The filename to fetch information from'),
                'value'     => $this->getResource()->get('filename', '')
            )
        );

        $this->addElement(
            'text',
            'resource_file_fields',
            array(
                'required'  => true,
                'label'     => t('Pattern'),
                'helptext'  => t('The regular expression by which to identify columns'),
                'value'     => $this->getResource()->get('fields', '')
            )
        );
    }

    protected function addNameFields()
    {
        $this->addElement(
            'text',
            'resource_all_name',
            array(
                'required'  => true,
                'label'     => t('Resource Name'),
                'helptext'  => t('The unique name of this resource'),
                'value'     => $this->getName()
            )
        );

        $this->addElement(
            'hidden',
            'resource_all_name_old',
            array(
                'value' => $this->getOldName()
            )
        );
    }

    /**
     * Add checkbox at the beginning of the form which allows to skip connection validation
     */
    protected function addForceCreationCheckbox()
    {
        $checkbox = new Zend_Form_Element_Checkbox(
            array(
                'order'     => 0,
                'name'      => 'resource_force_creation',
                'label'     => t('Force Changes'),
                'helptext'  => t('Check this box to enforce changes without connectivity validation')
            )
        );
        $checkbox->addDecorator(new HelpText());
        $this->addElement($checkbox);
    }

    /**
     * Add a select box for choosing the type to use for this backend
     */
    protected function addTypeSelectionBox()
    {
        $this->addElement(
            'select',
            'resource_type',
            array(
                'required'      => true,
                'label'         => t('Resource Type'),
                'helptext'      => t('The type of resource'),
                'value'         => $this->getResource()->type,
                'multiOptions'  => array(
                    'db'            => t('SQL Database'),
                    'ldap'          => 'LDAP',
                    'statusdat'     => 'Status.dat',
                    'livestatus'    => 'Livestatus',
                    'file'          => t('File')
                )
            )
        );
        $this->enableAutoSubmit(array('resource_type'));
    }

    /**
     * Validate this form with the Zend validation mechanism and perform a validation of the connection
     *
     * If validation fails, the 'resource_force_creation' checkbox is prepended to the form to allow users to
     * skip the connection validation
     *
     * @param   array   $data   The form input to validate
     *
     * @return  bool            True when validation succeeded, false if not
     */
    public function isValid($data)
    {
        if (!parent::isValid($data)) {
            return false;
        }
        if (isset($data['resource_force_creation']) && $data['resource_force_creation']) {
            return true;
        }
        if (!$this->isValidResource()) {
            $this->addForceCreationCheckbox();
            return false;
        }
        return true;
    }

    /**
     * Test if the changed resource is a valid resource, by instantiating it and
     * checking if a connection is possible
     *
     * @return  bool    True when a connection to the resource is possible
     */
    public function isValidResource()
    {
        return $this->resourceSubForm->isValidResource();

        // TODO: Find a way to handle this properly.
        try {
            switch ($config->type) {
                case 'db':
                    // TODO:
                case 'statusdat':
                    if (!file_exists($config->object_file) || !file_exists($config->status_file)) {
                        $this->addErrorMessage(
                            t('Connectivity validation failed, the provided file does not exist.')
                        );
                        return false;
                    }
                    break;
                case 'livestatus':
                    $resource = ResourceFactory::createResource($config);
                    $resource->connect()->disconnect();
                    break;
                case 'ldap':

                    break;
                case 'file':
                    if (!file_exists($config->filename)) {
                        $this->addErrorMessage(
                            t('Connectivity validation failed, the provided file does not exist.')
                        );
                        return false;
                    }
                    break;
            }
        } catch (Exception $e) {
            $this->addErrorMessage();
            return false;
        }

        return true;
    }

    public function create()
    {
        $this->addNameFields();
        $this->addTypeSelectionBox();

        switch ($this->getRequest()->getParam('resource_type', $this->getResource()->type)) {
            case 'db':
                $this->addDbForm();
                break;
            case 'statusdat':
                $this->addStatusdatForm();
                break;
            case 'livestatus':
                $this->addLivestatusForm();
                break;
            case 'ldap':
                $this->addLdapForm();
                break;
            case 'file':
                $this->addFileForm();
                break;
        }

        $this->setSubmitLabel('{{SAVE_ICON}} Save Changes');
    }

    /**
     * Return a configuration containing the backend settings entered in this form
     *
     * @return  Zend_Config     The updated configuration for this backend
     */
    public function getConfig()
    {
        $values = $this->getValues();

        $result = array();
        if (array_key_exists('resource_type', $values)) {
            $result['type'] = $values['resource_type'];
        }

        foreach ($values as $key => $value) {
            if ($key !== 'resource_type' && $key !== 'resource_all_name' && $key !== 'resource_all_name_old') {
                $configKey = explode('_', $key, 3);
                if (count($configKey) === 3) {
                    $result[$configKey[2]] = $value;
                }
            }
        }

        return new Zend_Config($result);
    }
}
