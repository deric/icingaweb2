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
use Icinga\Web\Form;
use Zend_Config;

/**
 * Contains the properties needed to create a basic LDAP resource.
 */
class LivestatusResourceForm extends ResourceBaseForm {

    public function create() {
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

    /**
     * Return the configuration for the livestatus resource defined by this form
     *
     * @return Zend_Config  The configuration
     */
    public function getConfig()
    {
        $values = $this->getValues();
        return new Zend_Config(array(
            'type'   => 'livestatus',
            'socket' => $values['resource_livestatus_socket']
        ));
    }

    /**
     * Return if this resource is usable by icingaweb
     *
     * Check if it is possible to connect to the provided socket.
     *
     * @return bool
     */
    public function isValidResource() {
        $config = $this->getConfig();
        try {
            $resource = ResourceFactory::createResource($config);
            $resource->connect()->disconnect();
        } catch (Exception $e) {
            $this->addErrorMessage(t('Connectivity validation failed, connection to the given resource not possible.'));
            return false;
        }
        return true;
    }
} 