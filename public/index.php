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

require_once realpath(__DIR__ . '/../library/Icinga/Application/functions.php');

if (!is_zend_installed('1')) {
    echo '<h3>Zend Framework not found!</h3>'
        . 'The Zend Framework 1 is mandatory to successfully install and run this application.'
        . ' Please go to <a href="http://framework.zend.com/downloads/latest#ZF1">zend.com</a>'
        . ' and install its latest version.';
    die;
}

/*
 * Set timezone before bootstrapping the application and therefore before calling `setupTimezone()` because in case an
 * error occurred whilst, the logger calls date/time functions which would generate a warning if the php.ini lacks a
 * valid timezone.
 */
date_default_timezone_set('UTC');

require_once realpath(__DIR__ . '/../library/Icinga/Application/ApplicationBootstrap.php');
require_once realpath(__DIR__ . '/../library/Icinga/Application/Wizard.php');
require_once realpath(__DIR__ . '/../library/Icinga/Application/Web.php');

use \Icinga\Application\Web;
use \Icinga\Application\Wizard;

$configDir = realpath(__DIR__ . '/../config/');
if (Web::isInstalled($configDir)) {
    Web::start($configDir)->dispatch();
} else {
    $logDir = realpath(__DIR__ . '/../var/log');
    Wizard::start($configDir, $logDir)->dispatch();
}
