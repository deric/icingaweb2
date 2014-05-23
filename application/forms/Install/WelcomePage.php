<?php
// @codeCoverageIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Install;

use Icinga\Web\Wizard\Page;
use Icinga\Application\Config;
use Icinga\Web\Form\Validator\TokenValidator;

class WelcomePage extends Page
{
    public function create()
    {
        $this->addNote(t('Welcome to the installation of Icingaweb 2!'), 1);

        $this->addElement(
            'password',
            'token',
            array(
                'required'      => true,
                'label'         => t('Please provide a valid authentication token to start with the installation:'),
                'validators'    => array(new TokenValidator(Config::$configDir . '/setup.token'))
            )
        );
    }
}
// @codeCoverageIgnoreEnd
