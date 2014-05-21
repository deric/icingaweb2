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

use Icinga\Data\ResourceFactory;
use Icinga\Web\Form;
use Zend_Config;

class ResourceBaseForm extends Form {

    protected $resource = null;

    /**
     * Set the resource configuration to edit
     *
     * @param   Zend_Config     $resource   The config to set
     */
    public function setResource(Zend_Config $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Get the current base resource
     *
     * @return Zend_Config  The resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Generate the configuration from all current form inputs.
     *
     * @return Zend_Config  The generated configuration
     */
    public function getConfig()
    {
        $values = $this->getData();
        $result = array();
        foreach ($values as $key => $value) {
            $configKey = explode('_', $key, 3);
            if (count($configKey) === 3) {
                $result[$configKey[2]] = $value;
            }
        }
        return new Zend_Config($result);
    }

    /**
     * Return if this resource is usable by icingaweb
     *
     * For abstract resources, this will perform a simple connectivity test
     *
     * @return bool
     */
    public function isValidResource($data) {
        $config = $this->createConfig($data);
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