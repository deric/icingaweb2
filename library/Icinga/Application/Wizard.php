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

namespace Icinga\Application;

require_once 'Zend/View.php';
require_once 'Zend/Layout.php';
require_once 'Zend/Controller/Front.php';
require_once 'Zend/Controller/Action/HelperBroker.php';

use \Zend_View;
use \Zend_Layout;
use \Zend_Controller_Front;
use \Zend_Controller_Action_HelperBroker;

class Wizard
{
    /**
     * The current instance of this wizard
     *
     * @var Wizard
     */
    private static $instance;

    /**
     * Application directory
     *
     * @var string
     */
    private $appDir;

    /**
     * Configuration directory
     *
     * @var string
     */
    private $configDir;

    /**
     * Logging directory
     *
     * @var string
     */
    private $logDir;

    /**
     * View object
     *
     * @var View
     */
    private $viewRenderer;

    /**
     * Zend front controller instance
     *
     * @var Zend_Controller_Front
     */
    private $frontController;

    /**
     * Return the current instance of this wizard
     *
     * @return  Wizard      The current instance or a new one
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Wizard();
        }
        return self::$instance;
    }

    /**
     * Return the actual application directory
     *
     * @return  string
     */
    public function getApplicationDir()
    {
        if (!isset($this->appDir)) {
            $this->appDir = realpath(__DIR__ . '/../../../installer');
        }
        return $this->appDir;
    }

    /**
     * Return the actual configuration directory
     *
     * @return  string
     */
    public function getConfigurationDir()
    {
        return $this->configDir;
    }

    /**
     * Return the actual logging directory
     *
     * @return  string
     */
    public function getLoggingDir()
    {
        return $this->logDir;
    }

    /**
     * Return the actual front controller
     *
     * @return  Zend_Controller_Front
     */
    public function getFrontController()
    {
        return $this->frontController;
    }

    /**
     * Return the actual view object
     *
     * @return  View
     */
    public function getViewRenderer()
    {
        return $this->viewRenderer;
    }

    /**
     * Start and setup a new install wizard
     *
     * @param   string      $configDir      The path to the configuration directory to use
     * @return  self
     */
    public static function start($configDir, $logDir)
    {
        $wizard = self::getInstance();
        $wizard->setup($configDir, $logDir);
        return $wizard;
    }

    /**
     * Finalise this wizard's initialisation
     *
     * @param   string      $configDir      The path to the configuration directory to use
     * @param   string      $logDir         The path to the logging directory to use
     */
    private function setup($configDir, $logDir)
    {
        Zend_Layout::startMvc(
            array(
                'layout'     => 'layout',
                'layoutPath' => $this->getApplicationDir() . '/layouts/scripts'
            )
        );
        $this->setupFrontController();
        $this->setupViewRenderer();
        $this->configDir = $configDir;
        $this->logDir = $logDir;
    }

    /**
     * Instantiate front controller
     */
    private function setupFrontController()
    {
        $this->frontController = Zend_Controller_Front::getInstance();
        $this->frontController->setControllerDirectory($this->getApplicationDir() . '/controllers');
        $this->frontController->setParams(
            array(
                'displayExceptions' => true
            )
        );
    }

    /**
     * Register helper paths and views for renderer
     */
    private function setupViewRenderer()
    {
        $view = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $view->setView(new Zend_View());
        $view->view->setEncoding('UTF-8');
        $view->view->addHelperPath($this->getApplicationDir() . '/views/helpers');

        $view->view->headTitle()->prepend('Icinga');
        $view->view->headTitle()->setSeparator(' :: ');

        $this->viewRenderer = $view;
    }

    /**
     * Dispatch public interface
     */
    public function dispatch()
    {
        $this->frontController->dispatch();
    }
}
