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

use Icinga\Form\Config\Resource\DbResourceForm;
use Icinga\Form\Config\Resource\FileResourceForm;
use Icinga\Form\Config\Resource\LdapResourceForm;
use Icinga\Form\Config\Resource\LivestatusResourceForm;
use Icinga\Form\Config\Resource\ResourceBaseForm;
use Icinga\Form\Config\Resource\StatusdatResourceForm;
use Zend_Config;
use Zend_Form_Element_Checkbox;
use Icinga\Web\Form;
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
     * @var ResourceBaseForm
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

    /**
     * Create the appropriate resource form for the currently selected resource type.
     *
     * @return DbResourceForm|FileResourceForm|LdapResourceForm|LivestatusResourceForm|StatusdatResourceForm
     */
    protected function createResourceForm()
    {
        switch ($this->getRequest()->getParam('resource_type', $this->getResource()->type)) {
            case 'db':
                $resource = new DbResourceForm();
                break;
            case 'statusdat':
                $resource = new StatusdatResourceForm();
                break;
            case 'livestatus':
                $resource = new LivestatusResourceForm();
                break;
            case 'ldap':
                $resource = new LdapResourceForm();
                break;
            case 'file':
                $resource = new FileResourceForm();
                break;
        }
        $resource->setResource($this->getResource());

        // no CSRF-token on subforms.
        $resource->setTokenDisabled();
        $resource->buildForm();
        return $resource;
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
                'value'         => $this->getRequest()->getParam('resource_type', $this->getResource()->type),
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

        $this->addElement(
            'hidden',
            'resource_type_old',
            array(
                'required' => true,
                'value' => $this->getRequest()->getParam('resource_type', $this->getResource()->type)
            )
        );
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
        foreach ($this->getElements() as $key => $element) {
            // Initialize all empty elements with their default values.
            if (!isset($data[$key])) {
                $data[$key] = $element->getValue();
            }
        }
        if (!parent::isValid($data)) {
            return false;
        }
        if (isset($data['resource_force_creation']) && $data['resource_force_creation']) {
            return true;
        }
        if ($data['resource_type_old'] === $data['resource_type']) {
            if (!$this->isValidResource($data)) {
                $this->addForceCreationCheckbox();
                return false;
            }
            return true;
        } else {
            $this->getElement('resource_type_old')->setValue($this->getRequest()->getParam('resource_type'));
            return false;
        }
    }

    /**
     * Test if the changed resource is a valid resource
     *
     * Calls the resource forms isValidResource() function, which in turn starts
     * resource-specific connectivity tests, to determine if this resource is usable.
     *
     * @return  bool    True when a connection to the resource is possible
     */
    public function isValidResource()
    {
        $valid = $this->resourceSubForm->isValidResource();
        $this->addErrorMessages($this->resourceSubForm->getErrorMessages());
        return $valid;
    }

    /**
     * Populate the form with all Zend_Form_Elements
     */
    public function create()
    {
        $this->addNameFields();
        $this->addTypeSelectionBox();
        $this->resourceSubForm = $this->createResourceForm();
        $this->addSubForm($this->resourceSubForm, 'subform_resource');
        $this->addElement(
            'button',
            'btn_submit',
            array(
                'type'   => 'submit',
                'escape' => false,
                'value'  => '1',
                'label'  => $this->getView()->icon('save.png', 'Save Changes')
                    . ' Save changes',
            )
        );
    }

    /**
     * Return a configuration containing the backend settings entered in this form
     *
     * @return  Zend_Config     The updated configuration for this backend
     */
    public function getConfig()
    {
        $values = $this->getValues();
        $result = $this->resourceSubForm->getConfig()->toArray();
        $result['type'] = $values['resource_type'];
        return new Zend_Config($result);
    }
}
