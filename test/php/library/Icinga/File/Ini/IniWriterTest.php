<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Config;

use Icinga\File\Ini\IniWriter;
use Icinga\Test\BaseTestCase;
use Icinga\Application\Config;

class IniWriterTest extends BaseTestCase
{
    protected $tempFile;
    protected $tempFile2;

    public function setUp()
    {
        parent::setUp();

        $this->tempFile = tempnam(sys_get_temp_dir(), 'icinga-ini-writer-test');
        $this->tempFile2 = tempnam(sys_get_temp_dir(), 'icinga-ini-writer-test-2');
    }

    public function tearDown()
    {
        parent::tearDown();

        unlink($this->tempFile);
        unlink($this->tempFile2);
    }

    public function testWhetherPointInSectionIsNotNormalized()
    {
        $writer = new IniWriter(
            array(
                'config' => Config::fromArray(
                        array(
                            'section' => array(
                                'foo.bar' => 1337
                            ),
                            'section.with.multiple.dots' => array(
                                'some more' => array(
                                    'nested stuff' => 'With more values'
                                )
                            )
                        )
                    ),
                'filename' => $this->tempFile
            )
        );
        $writer->write();
        $config = Config::fromIni($this->tempFile)->toArray();
        $this->assertTrue(array_key_exists('section.with.multiple.dots', $config), 'Section names not normalized');
    }

    public function testWhetherSimplePropertiesAreInsertedInEmptyFiles()
    {
        $this->markTestSkipped('Implementation has changed. Section-less properties are not supported anymore');
        $target = $this->writeConfigToTemporaryFile('');
        $config = Config::fromArray(array('key' => 'value'));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertEquals('value', $newConfig->get('key'), 'IniWriter does not insert in empty files');
    }

    public function testWhetherSimplePropertiesAreInsertedInExistingFiles()
    {
        $this->markTestSkipped('Implementation has changed. Section-less properties are not supported anymore');
        $target = $this->writeConfigToTemporaryFile('key1 = "1"');
        $config = Config::fromArray(array('key2' => '2'));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertEquals('2', $newConfig->get('key2'), 'IniWriter does not insert in existing files');
    }

    /**
     * @depends testWhetherSimplePropertiesAreInsertedInExistingFiles
     */
    public function testWhetherSimplePropertiesAreUpdated()
    {
        $this->markTestSkipped('Implementation has changed. Section-less properties are not supported anymore');
        $target = $this->writeConfigToTemporaryFile('key = "value"');
        $config = Config::fromArray(array('key' => 'eulav'));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertEquals('eulav', $newConfig->get('key'), 'IniWriter does not update simple properties');
    }

    /**
     * @depends testWhetherSimplePropertiesAreInsertedInExistingFiles
     */
    public function testWhetherSimplePropertiesAreDeleted()
    {
        $this->markTestSkipped('Implementation has changed. Section-less properties are not supported anymore');
        $target = $this->writeConfigToTemporaryFile('key = "value"');
        $config = new Config();
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertNull($newConfig->get('key'), 'IniWriter does not delete simple properties');
    }

    public function testWhetherNestedPropertiesAreInserted()
    {
        $target = $this->writeConfigToTemporaryFile('');
        $config = Config::fromArray(array('a' => array('b' => 'c')));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertInstanceOf(
            'Icinga\Data\ConfigObject',
            $newConfig->getSection('a'),
            'IniWriter does not insert nested properties'
        );
        $this->assertEquals(
            'c',
            $newConfig->getSection('a')->get('b'),
            'IniWriter does not insert nested properties'
        );
    }

    /**
     * @depends testWhetherNestedPropertiesAreInserted
     */
    public function testWhetherNestedPropertiesAreUpdated()
    {
        $this->markTestSkipped('Implementation has changed. Section-less properties are not supported anymore');
        $target = $this->writeConfigToTemporaryFile('a.b = "c"');
        $config = Config::fromArray(array('a' => array('b' => 'cc')));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertInstanceOf(
            get_class($newConfig),
            $newConfig->get('a'),
            'IniWriter does not update nested properties'
        );
        $this->assertEquals(
            'cc',
            $newConfig->get('a')->get('b'),
            'IniWriter does not update nested properties'
        );
    }

    /**
     * @depends testWhetherNestedPropertiesAreInserted
     */
    public function testWhetherNestedPropertiesAreDeleted()
    {
        $this->markTestSkipped('Implementation has changed. Section-less properties are not supported anymore');
        $target = $this->writeConfigToTemporaryFile('a.b = "c"');
        $config = new Config();
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertNull(
            $newConfig->get('a'),
            'IniWriter does not delete nested properties'
        );
    }

    public function testWhetherSimpleSectionPropertiesAreInserted()
    {
        $target = $this->writeConfigToTemporaryFile('');
        $config = Config::fromArray(array('section' => array('key' => 'value')));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertInstanceOf(
            'Icinga\Data\ConfigObject',
            $newConfig->getSection('section'),
            'IniWriter does not insert sections'
        );
        $this->assertEquals(
            'value',
            $newConfig->getSection('section')->get('key'),
            'IniWriter does not insert simple section properties'
        );
    }

    /**
     * @depends testWhetherSimpleSectionPropertiesAreInserted
     */
    public function testWhetherSimpleSectionPropertiesAreUpdated()
    {
        $target = $this->writeConfigToTemporaryFile(<<<'EOD'
[section]
key = "value"
EOD
        );
        $config = Config::fromArray(array('section' => array('key' => 'eulav')));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertEquals(
            'eulav',
            $newConfig->getSection('section')->get('key'),
            'IniWriter does not update simple section properties'
        );
    }

    /**
     * @depends testWhetherSimpleSectionPropertiesAreInserted
     */
    public function testWhetherSimpleSectionPropertiesAreDeleted()
    {
        $target = $this->writeConfigToTemporaryFile(<<<'EOD'
[section]
key = "value"
EOD
        );
        $config = Config::fromArray(array('section' => array()));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertNull(
            $newConfig->getSection('section')->get('key'),
            'IniWriter does not delete simple section properties'
        );
    }

    public function testWhetherNestedSectionPropertiesAreInserted()
    {
        $this->markTestSkipped('Implementation has changed. Config::fromIni cannot handle nested properties anymore');
        $target = $this->writeConfigToTemporaryFile('');
        $config = Config::fromArray(array('section' => array('a' => array('b' => 'c'))));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertInstanceOf(
            get_class($newConfig),
            $newConfig->get('section'),
            'IniWriter does not insert sections'
        );
        $this->assertInstanceOf(
            get_class($newConfig),
            $newConfig->get('section')->get('a'),
            'IniWriter does not insert nested section properties'
        );
        $this->assertEquals(
            'c',
            $newConfig->get('section')->get('a')->get('b'),
            'IniWriter does not insert nested section properties'
        );
    }

    /**
     * @depends testWhetherNestedSectionPropertiesAreInserted
     */
    public function testWhetherNestedSectionPropertiesAreUpdated()
    {
        $target = $this->writeConfigToTemporaryFile(<<<'EOD'
[section]
a.b = "c"
EOD
        );
        $config = Config::fromArray(array('section' => array('a' => array('b' => 'cc'))));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertEquals(
            'cc',
            $newConfig->get('section')->get('a')->get('b'),
            'IniWriter does not update nested section properties'
        );
    }

    /**
     * @depends testWhetherNestedSectionPropertiesAreInserted
     */
    public function testWhetherNestedSectionPropertiesAreDeleted()
    {
        $target = $this->writeConfigToTemporaryFile(<<<'EOD'
[section]
a.b = "c"
EOD
        );
        $config = Config::fromArray(array('section' => array()));
        $writer = new IniWriter(array('config' => $config, 'filename' => $target));
        $writer->write();

        $newConfig = Config::fromIni($target);
        $this->assertNull(
            $newConfig->get('section')->get('a'),
            'IniWriter does not delete nested section properties'
        );
    }

    public function testWhetherSectionOrderIsUpdated()
    {
        $config = <<<'EOD'
[one]
key1                = "1"
key2                = "2"

[two]
a.b                 = "c"
d.e                 = "f"

[three]
key                 = "value"
foo.bar             = "raboof"
EOD;

        $reverted = <<<'EOD'
[three]
key                 = "value"
foo.bar             = "raboof"

[two]
a.b                 = "c"
d.e                 = "f"

[one]
key1                = "1"
key2                = "2"
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            array(
                'config'    => Config::fromArray(
                    array(
                        'three' => array(
                            'foo' => array(
                                'bar' => 'raboof'
                            ),
                            'key' => 'value'
                        ),
                        'two'   => array(
                            'd' => array(
                                'e' => 'f'
                            ),
                            'a' => array(
                                'b' => 'c'
                            )
                        ),
                        'one'   => array(
                            'key2' => '2',
                            'key1' => '1'
                        )
                    )
                ),
                'filename'  => $target
            )
        );

        $this->assertEquals(
            trim($reverted),
            trim($writer->render()),
            'IniWriter does not preserve section and/or property order'
        );
    }

    public function testWhetherCommentOrderIsUpdated()
    {
        $config = <<<'EOD'
; comment 1
[one]

; comment 2
[two]
EOD;

        $reverted = <<<'EOD'
; comment 2
[two]

; comment 1
[one]
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            array(
                'config' => Config::fromArray(
                    array(
                        'two' => array(),
                        'one' => array()
                    )
                ),
                'filename'  => $target
            )
        );

        $this->assertEquals(
            trim($reverted),
            trim($writer->render()),
            'IniWriter does not preserve section and/or property order'
        );
    }


    public function testWhetherCommentsOnEmptyLinesArePreserved()
    {
        $config = <<<'EOD'
; some interesting comment
key                 = "value"
; another interesting comment
; boring comment
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            array('config' => Config::fromArray(array('key' => 'value')), 'filename' => $target)
        );

        $this->assertEquals(
            trim($config),
            trim($writer->render()),
            'IniWriter does not preserve comments on empty lines'
        );
    }

    public function testWhetherCommentsOnPropertyLinesArePreserved()
    {
        $config = <<<'EOD'
foo                 = 1337                  ; I know what a " and a ' is
bar                 = 7331                  ; I; tend; to; overact; !1!1!!11!111!                                      ;
key                 = "value"               ; some comment for a small sized property
xxl                 = "very loooooooooooooooooooooong" ; my value is very lo...
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            array(
                'config' => Config::fromArray(
                    array(
                        'foo' => 1337,
                        'bar' => 7331,
                        'key' => 'value',
                        'xxl' => 'very loooooooooooooooooooooong'
                    )
                ),
                'filename' => $target
            )
        );

        $this->assertEquals(
            trim($config),
            trim($writer->render()),
            'IniWriter does not preserve comments on property lines'
        );
    }

    public function testWhetherCommentsOnEmptySectionLinesArePreserved()
    {
        $config = <<<'EOD'
[section]
; some interesting comment, in a section
key                 = "value"
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            array('config' => Config::fromArray(array('section' => array('key' => 'value'))), 'filename' => $target)
        );

        $this->assertEquals(
            trim($config),
            trim($writer->render()),
            'IniWriter does not preserve comments on empty section lines'
        );
    }

    public function testWhetherCommentsOnSectionPropertyLinesArePreserved()
    {
        $config = <<<'EOD'
[section]
foo                 = 1337                  ; I know what a " and a ' is
bar                 = 7331                  ; I; tend; to; overact; !1!1!!11!111!                                      ;
key                 = "value"               ; some comment for a small sized property
xxl                 = "very loooooooooooooooooooooong" ; my value is very lo...
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            array(
                'config' => Config::fromArray(
                    array(
                        'section' => array(
                            'foo' => 1337,
                            'bar' => 7331,
                            'key' => 'value',
                            'xxl' => 'very loooooooooooooooooooooong'
                        )
                    )
                ),
                'filename' => $target
            )
        );

        $this->assertEquals(
            trim($config),
            trim($writer->render()),
            'IniWriter does not preserve comments on property lines'
        );
    }

    public function testWhetherLinebreaksAreRemoved()
    {
        $target = $this->writeConfigToTemporaryFile('');
        $writer = new IniWriter(
            array(
                'config' => Config::fromArray(
                    array(
                        'section' => array(
                            'foo' => 'linebreak
in line',
                            'linebreak
inkey' => 'blarg'
                        )
                    )
                ),
                'filename' => $target
            )
        );

        $rendered = $writer->render();
        $this->assertEquals(
            count(explode("\n", $rendered)),
            4,
            'generated config should not contain more than three line breaks'
        );
    }

    public function testSectionNameEscaping()
    {
        $config = <<<'EOD'
[section 1]
foo                 = 1337

[section (with special chars)]
foo                 = "baz"

[section/as/arbitrary/path]
foo                 = "nope"

[section.with.dots.in.it]
foo                 = "bar"
EOD;
        $target = $this->writeConfigToTemporaryFile($config);
        $writer = new IniWriter(
            array(
                'config' => Config::fromArray(
                    array(
                        'section 1' => array('foo' => 1337),
                        'section (with special chars)' => array('foo' => 'baz'),
                        'section/as/arbitrary/path' => array('foo' => 'nope'),
                        'section.with.dots.in.it' => array('foo' => 'bar')
                    )
                ),
                'filename' => $target
            )
        );

        $this->assertEquals(
            trim($config),
            trim($writer->render()),
            'IniWriter does not handle special chars in section names properly.'
        );
    }

    /**
     * Write a INI-configuration string to a temporary file and return its path
     *
     * @param   string      $config     The config string to write
     *
     * @return  string                  The path to the temporary file
     */
    protected function writeConfigToTemporaryFile($config)
    {
        file_put_contents($this->tempFile, $config);
        return $this->tempFile;
    }
}
