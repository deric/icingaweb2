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

namespace Icinga\Installer;

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
     * Initialise a new wizard
     *
     * @param   string      $configDir      The path to the configuration directory to use
     */
    public function __construct($configDir)
    {
        $this->configDir = $configDir;
    }

    /**
     * Return the actual application directory
     *
     * @return  string
     */
    public function getApplicationDir()
    {
        if (!isset($this->appDir)) {
            $this->appDir = realpath('Application');
        }
        return $this->appDir;
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
    public static function start($configDir)
    {
        $wizard = new Wizard($configDir);
        $wizard->setup();
        return $wizard;
    }

    /**
     * Finalise this wizard's initialisation
     */
    private function setup()
    {
        Zend_Layout::startMvc(
            array(
                'layout'     => 'layout',
                'layoutPath' => $this->getApplicationDir() . '/layouts/scripts'
            )
        );
        $this->setupFrontController();
        $this->setupViewRenderer();
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
