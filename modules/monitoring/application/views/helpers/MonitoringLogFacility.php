<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Icinga 2 Web - Head for multiple monitoring frontends
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Convert icinga log facilities into human readable values
 */
class Zend_View_Helper_MonitoringLogFacility extends Zend_View_Helper_Abstract
{
    /**
     * Binary flags
     *
     * @var array
     */
    private $logFlags = array(
        'Runtime Error'             => 1,
        'Runtime Warning'           => 2,
        'Verification Error'        => 4,
        'Verification Warning'      => 8,
        'Config Error'              => 16,
        'Config Warning'            => 32,
        'Process Info'              => 64,
        'Event Handler'             => 128,
        'Notification'              => 256,
        'External Command'          => 512,
        'Host Up'                   => 1024,
        'Host Down'                 => 2048,
        'Host Unreachable'          => 4096,
        'Service Ok'                => 8192,
        'Service Unknown'           => 16384,
        'Service Warning'           => 32768,
        'Service Critical'          => 65536,
        'Passive Check'             => 131072,
        'Info Message'              => 262144,
        'Host Notification'         => 524288,
        'Service Notification'      => 1048576
    );

    /**
     * Helper dispatch
     *
     * @return self
     */
    public function monitoringLogFacility()
    {
        return $this;
    }

    /**
     * Convert log flags into human readable values
     *
     * @param   int   $binaryFlag   Integer value
     *
     * @return  string              Comma separated list of types
     */
    public function readableValue($binaryFlag)
    {
        $out = array();
        $binaryFlag = (int)$binaryFlag;
        foreach ($this->logFlags as $desc => $flag) {
            if (($binaryFlag & $flag) === $flag) {
                $out[] = $desc;
            }
        }
        if (count($out) === 0) {
            return 'Unknown';
        } else {
            return implode(', ', $out);
        }
    }
}
// @codingStandardsIgnoreStop