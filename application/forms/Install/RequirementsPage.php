<?php
// @codeCoverageIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Install;

use Zend_Config;
use Icinga\Web\Wizard\Page;
use Icinga\Application\Platform;

class RequirementsPage extends Page
{
    /**
     * The information used to check requirements
     *
     * @var array
     */
    protected $info;

    /**
     * The requirement state information
     *
     * @var array
     */
    protected $report;

    /**
     * Render this RequirementsPage
     *
     * @return  string
     *
     * @todo    Implement this as FormElement
     */
    public function render()
    {
        $info = $this->getInfo();
        $report = $this->getReport();

        $rowTemplate = '<tr><td class="title">%s</td><td class="description">%s</td><td class="state %s">%s</td></tr>';
        $tableBody = sprintf(
            $rowTemplate,
            t('PHP Version'),
            t(
                'Running Icingaweb requires PHP version 5.3.2. Advanced features' .
                ' like the built-in web server require PHP version 5.4.'
            ),
            $report['php_version_matches'] === -1 ? 'critical' : 'ok',
            sprintf(t('You are running PHP version %s.'), $info['php_version'])
        );
        $tableBody .= sprintf(
            $rowTemplate,
            t('Linux Platform'),
            t(
                'Icingaweb is developed for and tested on Linux. While we cannot' .
                ' guarantee they will, other platforms may also perform as well.'
            ),
            $report['php_runs_on_linux'] === 0 ? 'warning' : 'ok',
            sprintf(t('You are running PHP on a %s system.'), $info['os'])
        );
        $tableBody .= sprintf(
            $rowTemplate,
            t('PHP Module: POSIX'),
            t(
                'It is strongly suggested to install/enable the POSIX module for PHP. While ' .
                'it is not required for the web frontend it is essential for the Icinga CLI.'
            ),
            $report['php_mod_posix_found'] === 0 ? 'warning' : 'ok',
            $report['php_mod_posix_found'] === 1 ? t('The PHP module POSIX is available.') : (
                t('The PHP module POSIX is missing.')
            )
        );
        $tableBody .= sprintf(
            $rowTemplate,
            t('PHP Module: JSON'),
            t('The JSON module for PHP is required for various export functionalities as well as APIs.'),
            $report['php_mod_json_found'] === 0 ? 'warning' : 'ok',
            $report['php_mod_json_found'] === 1 ? t('The PHP module JSON is available.') : (
                t('The PHP module JSON is missing.')
            )
        );
        $tableBody .= sprintf(
            $rowTemplate,
            t('PHP Module: PDO'),
            t(
                'Though Icingaweb can be operated without any database access, it is recommended to install/enable' .
                ' the PDO module for PHP to gain a significant performance increase as well as more flexibility.'
            ),
            $report['php_mod_pdo_found'] === 0 ? 'warning' : 'ok',
            $report['php_mod_pdo_found'] === 1 ? t('The PHP module PDO is available.') : (
                t('The PHP module PDO is missing.')
            )
        );
        $tableBody .= sprintf(
            $rowTemplate,
            t('Zend Database Adapter For MySQL'),
            t('The Zend database adapter for MySQL is required to access a MySQL database.'),
            $report['zend_db_mysql_found'] === 0 ? 'warning' : 'ok',
            $report['zend_db_mysql_found'] === 1 ? t('The Zend database adapter for MySQL is available.') : (
                t('The Zend database adapter for MySQL is missing.')
            )
        );
        $tableBody .= sprintf(
            $rowTemplate,
            t('Zend Database Adapter For PostgreSQL'),
            t('The Zend database adapter for PostgreSQL is required to access a PostgreSQL database.'),
            $report['zend_db_pgsql_found'] === 0 ? 'warning' : 'ok',
            $report['zend_db_pgsql_found'] === 1 ? t('The Zend database adapter for PostgreSQL is available.') : (
                t('The Zend database adapter for PostgreSQL is missing.')
            )
        );
        $tableBody .= sprintf(
            $rowTemplate,
            t('Default Timezone'),
            t('It is required that a default timezone has been set using date.timezone in php.ini.'),
            $report['default_timezone_set'] === -1 ? 'critical' : 'ok',
            $report['default_timezone_set'] === -1 ? t('You did not define a default timezone.') : (
                sprintf(t('Your default timezone is: %s'), $info['default_timezone'])
            )
        );
        $tableBody .= sprintf(
            $rowTemplate,
            t('Writable Config Directory'),
            t(
                'The Icingaweb configuration directory defaults to "/etc/icingaweb", if' .
                ' not explicitly set in the environment variable "ICINGAWEB_CONFIGDIR".'
            ),
            $report['config_dir_writable'] === -1 ? 'critical' : 'ok',
            sprintf(
                $report['config_dir_writable'] === 1 ? t('The current configuration directory is writable: %s') : (
                    t('The current configuration directory is not writable: %s')
                ),
                $info['web_config_dir']
            )
        );

        if (!$this->isValid()) {
            // This only works because a page is rendered before any direct wizard elements. Too hackish?
            $this->wizard->getElement('btn_advance')->setAttrib('disabled', 1);
        }

        return '<table class="requirements"><tbody>' . $tableBody . '</tbody></table>';
    }

    /**
     * Return whether all mandatory requirements are met
     *
     * @param   array   $data   The values to check (unused)
     *
     * @return  bool
     */
    public function isValid($data = null)
    {
        $report = $this->getReport();
        $requestData = $this->getRequest()->getParams();
        return (isset($requestData['btn_return']) &&
            !isset($requestData['btn_advance'])) ||
            ($report['php_version_matches'] === 1 &&
            $report['default_timezone_set'] === 1 &&
            $report['config_dir_writable'] === 1);
    }

    /**
     * Return the collected information that is used to check the requirements
     *
     * @return  Zend_Config
     */
    public function getConfig()
    {
        return new Zend_Config($this->getInfo());
    }

    /**
     * Collect and return the state of all requirements
     *
     * @return  array
     */
    protected function getReport()
    {
        if ($this->report === null) {
            $info = $this->getInfo();
            $this->report = array();
            $this->report['php_version_matches'] = version_compare($info['php_version'], '5.3.2', '>=') ? 1 : -1;
            $this->report['php_runs_on_linux'] = strtoupper(substr($info['os'], 0, 5)) === 'LINUX' ? 1 : 0;
            $this->report['php_mod_posix_found'] = $info['php_mod_posix_found'] ? 1 : 0;
            $this->report['php_mod_json_found'] = $info['php_mod_json_found'] ? 1 : 0;
            $this->report['php_mod_pdo_found'] = $info['php_mod_pdo_found'] ? 1 : 0;
            $this->report['zend_db_mysql_found'] = $info['zend_db_mysql_found'] ? 1 : 0;
            $this->report['zend_db_pgsql_found'] = $info['zend_db_pgsql_found'] ? 1 : 0;
            $this->report['default_timezone_set'] = $info['default_timezone'] ? 1 : -1;
            $this->report['config_dir_writable'] = is_writable($info['web_config_dir']) ? 1 : -1;
        }

        return $this->report;
    }

    /**
     * Collect and return the necessary information in order to verify that all requirements are met
     *
     * @return  array
     */
    protected function getInfo()
    {
        if ($this->info === null) {
            $this->info = array();
            $this->info['os'] = Platform::getOperatingSystemName();
            $this->info['php_version'] = Platform::getPhpVersion();
            $this->info['php_mod_posix_found'] = Platform::phpHasModule('posix');
            $this->info['php_mod_json_found'] = Platform::phpHasModule('json');
            $this->info['php_mod_pdo_found'] = Platform::phpHasModule('pdo');
            $this->info['zend_db_mysql_found'] = Platform::zendClassExists('Zend_Db_Adapter_Pdo_Mysql');
            $this->info['zend_db_pgsql_found'] = Platform::zendClassExists('Zend_Db_Adapter_Pdo_Pgsql');
            $this->info['default_timezone'] = Platform::getTimezone();

            if (array_key_exists('ICINGAWEB_CONFIGDIR', $_SERVER)) {
                $this->info['web_config_dir'] = realpath($_SERVER['ICINGAWEB_CONFIGDIR']);
            } elseif (array_key_exists('ICINGAWEB_CONFIGDIR', $_ENV)) {
                $this->info['web_config_dir'] = realpath($_ENV['ICINGAWEB_CONFIGDIR']);
            } else {
                $this->info['web_config_dir'] = '/etc/icingaweb';
            }
        }

        return $this->info;
    }
}
// @codeCoverageIgnoreEnd
