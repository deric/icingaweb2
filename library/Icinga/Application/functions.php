<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

if (function_exists('_')) {
    function t($messageId = null)
    {
        $msg = _($messageId);
        if (! $msg) {
            return $messageId;
        }
        return $msg;
    }

    function mt($domain, $messageId = null)
    {
        $msg = dgettext($domain, $messageId);
        if (! $msg) {
            return $messageId;
        }
        return $msg;
    }
} else {
    function t($messageId = null)
    {
        return $messageId;
    }

    function mt($domain, $messageId = null)
    {
        return $messageId;
    }
}

/**
 * Return whether a specific version of Zend is installed
 *
 * @param   string      $version    The version of Zend to search for
 * @return  bool                    Whether the given Zend version is installed
 */
function is_zend_installed($version)
{
    $phpIncludePaths = explode(PATH_SEPARATOR, get_include_path());

    foreach ($phpIncludePaths as $path)
    {
        if (@include_once($path . '/Zend/Version.php')) {
            if (substr(Zend_Version::VERSION, 0, strlen($version)) === $version) {
                return true;
            }
        }
    }

    return false;
}
