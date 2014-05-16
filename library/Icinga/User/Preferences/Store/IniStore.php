<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\User\Preferences\Store;

use Zend_Config;
use Icinga\Util\File;
use Icinga\Config\PreservingIniWriter;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;

/**
 * Load and save user preferences from and to INI files
 *
 * Usage example:
 * <code>
 * <?php
 *
 * use Zend_Config;
 * use Icinga\User\Preferences;
 * use Icinga\User\Preferences\PreferencesStore;
 * use Icinga\User\Preferences\Store\IniStore;
 *
 * // Create the store from the factory (preferred approach)
 * $store = new PreferencesStore(
 *     new Zend_Config(
 *         'type'       => 'ini',
 *         'location'   => '/path/to/preferences'
 *     ),
 *     $user // Instance of \Icinga\User
 * );
 *
 * // Create the store directly
 * $store = new IniStore(
 *     new Zend_Config(
 *         'location' => '/path/to/preferences'
 *     ),
 *     $user // Instance of \Icinga\User
 * );
 *
 * $preferences = new Preferences($store->load());
 * $preferences->aPreference = 'value';
 * $store->save($preferences);
 * </code>
 */
class IniStore extends PreferencesStore
{
    /**
     * Preferences file of the given user
     *
     * @var string
     */
    protected $preferencesFile;

    /**
     * Stored preferences
     *
     * @var array
     */
    protected $preferences = array();

    /**
     * Writer which stores the preferences
     *
     * @var PreservingIniWriter
     */
    protected $writer;

    /**
     * Initialize the store
     */
    protected function init()
    {
        $this->preferencesFile = sprintf(
            '%s/%s.ini',
            $this->getStoreConfig()->location,
            $this->getUser()->getUsername()
        );
    }

    /**
     * Load preferences from source
     *
     * @return  array
     *
     * @throws  NotReadableError    When the INI file of the user exists and is not readable
     */
    public function load()
    {
        if (file_exists($this->preferencesFile)) {
            if (!is_readable($this->preferencesFile)) {
                throw new NotReadableError(
                    'Preferences INI file ' . $this->preferencesFile . ' for user '
                    . $this->getUser()->getUsername() . ' is not readable'
                );
            } else {
                $this->preferences = parse_ini_file($this->preferencesFile);
            }
        }

        return $this->preferences;
    }

    /**
     * Save the given preferences
     *
     * @param   Preferences     $preferences    The preferences to save
     */
    public function save(Preferences $preferences)
    {
        $preferences = $preferences->toArray();
        $this->update(
            array_merge(
                array_diff_key($preferences, $this->preferences),
                array_intersect_key($preferences, $this->preferences)
            )
        );
        $this->delete(array_keys(array_diff_key($this->preferences, $preferences)));
        $this->write();
    }

    /**
     * Write the preferences
     *
     * @throws  NotWritableError    In case the INI file cannot be written
     */
    public function write()
    {
        if ($this->writer === null) {
            if (!file_exists($this->preferencesFile)) {
                if (!is_writable($this->getStoreConfig()->location)) {
                    throw new NotWritableError(
                        sprintf(
                            'Path to the preferences INI files %s is not writable',
                            $this->getStoreConfig()->location
                        )
                    );
                }

                File::create($this->preferencesFile);
            }

            if (!is_writable($this->preferencesFile)) {
                throw new NotWritableError(
                    'Preferences INI file ' . $this->preferencesFile . ' for user '
                    . $this->getUser()->getUsername() . ' is not writable'
                );
            }

            $this->writer = new PreservingIniWriter(
                array(
                    'config'    => new Zend_Config($this->preferences),
                    'filename'  => $this->preferencesFile
                )
            );
        }

        $this->writer->write();
    }

    /**
     * Add or update the given preferences
     *
     * @param   array   $preferences    The preferences to set
     */
    protected function update(array $preferences)
    {
        foreach ($preferences as $key => $value) {
            $this->preferences[$key] = $value;
        }
    }

    /**
     * Delete the given preferences by name
     *
     * @param   array   $preferenceKeys     The preference names to delete
     */
    protected function delete(array $preferenceKeys)
    {
        foreach ($preferenceKeys as $key) {
            unset($this->preferences[$key]);
        }
    }
}
