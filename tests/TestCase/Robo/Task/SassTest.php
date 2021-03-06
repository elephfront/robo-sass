<?php
/**
 * Copyright (c) Yves Piquel (http://www.havokinspiration.fr)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Yves Piquel (http://www.havokinspiration.fr)
 * @link          http://github.com/elephfront/robo-sass
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Elephfront\RoboSass\Tests;

use Elephfront\RoboSass\Task\Sass;
use Elephfront\RoboSass\Tests\Utility\MemoryLogger;
use PHPUnit\Framework\TestCase;
use Robo\Result;
use Robo\Robo;
use Robo\State\Data;

/**
 * Class CssMinifyTest
 *
 * Test cases for the CssMinify Robo task.
 */
class SassTest extends TestCase
{

    /**
     * Instance of the task that will be tested.
     *
     * @var \Elephfront\RoboSass\Task\Sass
     */
    protected $task;

    /**
     * setUp.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Robo::setContainer(Robo::createDefaultContainer());
        $this->task = new Sass();
        $this->task->setLogger(new MemoryLogger());
        if (file_exists(TESTS_ROOT . 'app' . DS . 'scss' . DS . 'output.css')) {
            unlink(TESTS_ROOT . 'app' . DS . 'scss' . DS . 'output.css');
        }
        if (file_exists(TESTS_ROOT . 'app' . DS . 'scss' . DS . 'deep' . DS . 'output-complex.css')) {
            unlink(TESTS_ROOT . 'app' . DS . 'scss' . DS . 'deep' . DS . 'output-complex.css');
        }
    }

    /**
     * tearDown.
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->task);
    }

    /**
     * Tests that giving the task no destinations map will throw an exception.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Impossible to run the Sass task without a destinations map.
     * @return void
     */
    public function testNoDestinationsMap()
    {
        $this->task->run();
    }

    /**
     * Tests that giving the task a destinations map with an invalid source file will throw an exception.
     *
     * @return void
     */
    public function testInexistantSource()
    {
        $this->task->setDestinationsMap([
            'bogus' => 'bogus'
        ]);
        $result = $this->task->run();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::EXITCODE_ERROR, $result->getExitCode());
        $this->assertEquals(
            'Impossible to find source file `bogus`',
            $result->getMessage()
        );
    }

    /**
     * Test a basic minification (with a set source map)
     *
     * @return void
     */
    public function testBasicCompilation()
    {
        $basePath = TESTS_ROOT . 'app' . DS . 'scss' . DS;
        $this->task->setDestinationsMap([
            $basePath . 'simple.scss' => $basePath . 'output.css'
        ]);
        $result = $this->task->run();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::EXITCODE_OK, $result->getExitCode());

        $this->assertEquals(
            file_get_contents(TESTS_ROOT . 'comparisons' . DS . __FUNCTION__ . '.css'),
            file_get_contents($basePath . 'output.css')
        );

        $source = $basePath . 'simple.scss';
        $dest = $basePath . 'output.css';
        $expectedLog = 'Compiled SASS from <info>' . $source . '</info> to <info>' . $dest . '</info>';
        $this->assertEquals(
            $expectedLog,
            $this->task->logger()->getLogs()[0]
        );
    }
    
    /**
     * Test an import with the writeFile feature disabled.
     *
     * @return void
     */
    public function testImportNoWrite()
    {
        $basePath = TESTS_ROOT . 'app' . DS . 'scss' . DS;
        $this->task->setDestinationsMap([
            $basePath . 'simple.scss' => $basePath . 'output.css'
        ]);
        $this->task->disableWriteFile();
        $result = $this->task->run();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::EXITCODE_OK, $result->getExitCode());

        $this->assertFalse(file_exists($basePath . 'output.css'));

        $source = $basePath . 'simple.scss';
        $expectedLog = 'Compiled SASS from <info>' . $source . '</info>';
        $this->assertEquals(
            $expectedLog,
            $this->task->logger()->getLogs()[0]
        );
    }

    /**
     * Tests that the task returns an error in case the file can not be written if normal mode
     *
     * @return void
     */
    public function testImportError()
    {
        $basePath = TESTS_ROOT . 'app' . DS . 'scss' . DS;
        $this->task = $this->getMockBuilder(Sass::class)
            ->setMethods(['writeFile'])
            ->getMock();
        $this->task->setLogger(new MemoryLogger());

        $this->task->method('writeFile')
            ->willReturn(false);

        $data = new Data();
        $data->mergeData([
            $basePath . 'simple.scss' => [
                'css' => "\$color: #24292E;\n\nbody {\n\tbackground-color: \$color\n}",
                'destination' => $basePath . 'output.css'
            ]
        ]);
        $this->task->receiveState($data);
        $result = $this->task->run();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::EXITCODE_ERROR, $result->getExitCode());

        $log = 'An error occurred while writing the destination file for source file `' . $basePath . 'simple.scss`';
        $this->assertEquals(
            $log,
            $result->getMessage()
        );
    }

    /**
     * Test a basic import using the chained state.
     *
     * @return void
     */
    public function testImportWithChainedState()
    {
        $basePath = TESTS_ROOT . 'app' . DS . 'scss' . DS;
        $data = new Data();
        $data->mergeData([
            $basePath . 'simple.scss' => [
                'css' => "\$color: #24292E;\n\nbody {\n\tbackground-color: \$color\n}",
                'destination' => $basePath . 'output.css'
            ]
        ]);
        $this->task->receiveState($data);
        $result = $this->task->run();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::EXITCODE_OK, $result->getExitCode());

        $resultData = $result->getData();
        $expected = [
            $basePath . 'simple.scss' => [
                'css' => "body {\n  background-color: #24292E; }\n",
                'destination' => $basePath . 'output.css'
            ]
        ];

        $this->assertTrue(is_array($resultData));
        $this->assertEquals($expected, $resultData);
    }

    /**
     * Test an import with a source map containing multiple files.
     *
     * @return void
     */
    public function testMultipleSourcesImport()
    {
        $basePath = TESTS_ROOT . 'app' . DS . 'scss' . DS;
        $desinationsMap = [
            $basePath . 'simple.scss' => $basePath . 'output.css',
            $basePath . 'more-complex.scss' => $basePath . 'deep' . DS . 'output-complex.css'
        ];

        $comparisonsMap = [
            $basePath . 'simple.scss' => TESTS_ROOT . 'comparisons' . DS . 'testBasicCompilation.css',
            $basePath . 'more-complex.scss' => TESTS_ROOT . 'comparisons' . DS . 'testComplexCompilation.css'
        ];

        $this->task->setDestinationsMap($desinationsMap);
        $result = $this->task->run();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::EXITCODE_OK, $result->getExitCode());

        foreach ($desinationsMap as $source => $destination) {
            $this->assertEquals(
                file_get_contents($comparisonsMap[$source]),
                file_get_contents($destination)
            );
        }

        $sentenceStart = 'Compiled SASS from';

        $source = $basePath . 'simple.scss';
        $destination = $basePath . 'output.css';
        $expectedLog = $sentenceStart . ' <info>' . $source . '</info> to <info>' . $destination . '</info>';
        $this->assertEquals(
            $expectedLog,
            $this->task->logger()->getLogs()[0]
        );

        $source = TESTS_ROOT . 'app' . DS . 'scss' . DS . 'more-complex.scss';
        $destination = TESTS_ROOT . 'app' . DS . 'scss' . DS . 'deep' . DS . 'output-complex.css';
        $expectedLog = $sentenceStart . ' <info>' . $source . '</info> to <info>' . $destination . '</info>';
        $this->assertEquals(
            $expectedLog,
            $this->task->logger()->getLogs()[1]
        );
    }
}
