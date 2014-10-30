<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Installation;

use Exception;
use Zend_Config;
use Icinga\Web\Setup\Step;
use Icinga\Application\Config;
use Icinga\Config\PreservingIniWriter;

class SecurityStep extends Step
{
    protected $data;

    protected $error;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function apply()
    {
        $config = array();
        $config['security'] = $this->data['securityConfig'];

        try {
            $writer = new PreservingIniWriter(array(
                'config'    => new Zend_Config($config),
                'filename'  => Config::resolvePath('modules/monitoring/config.ini'),
                'filemode'  => octdec($this->data['fileMode'])
            ));
            $writer->write();
        } catch (Exception $e) {
            $this->error = $e;
            return false;
        }

        $this->error = false;
        return true;
    }

    public function getSummary()
    {
        $pageTitle = '<h2>' . mt('monitoring', 'Monitoring Security') . '</h2>';
        $pageDescription = '<p>' . mt(
            'monitoring',
            'Icinga Web 2 will protect your monitoring environment against'
            . ' prying eyes using the configuration specified below:'
        ) . '</p>';

        $pageHtml = ''
            . '<table>'
            . '<tbody>'
            . '<tr>'
            . '<td><strong>' . mt('monitoring', 'Protected Custom Variables') . '</strong></td>'
            . '<td>' . $this->data['securityConfig']['protected_customvars'] . '</td>'
            . '</tr>'
            . '</tbody>'
            . '</table>';

        return $pageTitle . '<div class="topic">' . $pageDescription . $pageHtml . '</div>';
    }

    public function getReport()
    {
        if ($this->error === false) {
            $message = mt('monitoring', 'Monitoring security configuration has been successfully created: %s');
            return '<p>' . sprintf($message, Config::resolvePath('modules/monitoring/config.ini')) . '</p>';
        } elseif ($this->error !== null) {
            $message = mt(
                'monitoring',
                'Monitoring security configuration could not be written to: %s; An error occured:'
            );
            return '<p class="error">' . sprintf($message, Config::resolvePath('modules/monitoring/config.ini'))
                . '</p><p>' . $this->error->getMessage() . '</p>';
        }
    }
}