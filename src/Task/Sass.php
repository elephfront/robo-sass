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
declare(strict_types=1);
namespace Elephfront\RoboSass\Task;

use InvalidArgumentException;
use Robo\Contract\TaskInterface;
use Robo\Result;
use Robo\State\Consumer;
use Robo\State\Data;
use Robo\Task\BaseTask;

class Sass extends BaseTask implements TaskInterface, Consumer
{

    /**
     * List of the destinations files mapped by the sources name. One source equals one destination.
     *
     * @var array
     */
    protected $destinationsMap = [];

    /**
     * Instance of the Sass object (from the Sass extension).
     * 
     * @var Sass
     */
    protected $sass;

    /**
     * Data that was received from the previous task.
     * This array can stay empty if this task if the first to be run.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Data that will be passed to the next task with the Robo response.
     *
     * @var array
     */
    protected $returnData = [];

    /**
     * Constructor. Will bind the destinations map.
     *
     * @param array $destinationsMap Key / value pairs array where the key is the source and the value the destination.
     */
    public function __construct(array $destinationsMap = [])
    {
        $this->sass = new \Sass();
        $this->setDestinationsMap($destinationsMap);
    }

    /**
     * Sets the destinations map.
     *
     * @param array $destinationsMap Key / value pairs array where the key is the source and the value the destination.
     * @return self
     */
    public function setDestinationsMap(array $destinationsMap = [])
    {
        $this->destinationsMap = $destinationsMap;

        return $this;
    }

    /**
     * Runs the tasks : will replace all import statements from the source files from the `self::$destinationsMap` and
     * write them to the destinations file from the `self::$destinationsMap`.
     *
     * @return \Robo\Result Result object from Robo
     * @throws \InvalidArgumentException If no destinations map has been found.
     */
    public function run()
    {
        if ($this->data) {
            $exec = $this->processInlineData($this->data);
        } else {
            if (empty($this->destinationsMap)) {
                throw new InvalidArgumentException(
                    'Impossible to run the Sass task without a destinations map.'
                );
            }

            $exec = $this->processDestinationsMap($this->destinationsMap);
        }

        if ($exec !== true) {
            return Result::error(
                $this,
                sprintf('An error occurred while writing the destination file for source file `%s`', $exec)
            );
        } else {
            return Result::success($this, 'All SASS files has been compiled.', $this->returnData);
        }
    }

    /**
     * Execute the Sass compilation if we are dealing with a source maps (key = source file / value = destination)
     *
     * @param array $destinationsMap List of the destinations files mapped by the sources name. One source equals one
     * destination.
     * @return bool|string True if everything went ok, error otherwise.
     */
    protected function processDestinationsMap($destinationsMap)
    {
        $exec = true;

        foreach ($destinationsMap as $source => $destination) {
            $css = $this->sass->compileFile($source);
            $exec = $this->finishCompilation($css, $source, $destination);
        }

        return $exec;
    }

    /**
     * Execute the Sass compilation if we are dealing with raw CSS content (from another task).
     *
     * @param array $data Key : source file. Value : array with two keys :
     * - *css* : raw Sass content to minify
     * - *destination* : the destination of the processed content.
     * @return bool|string True if everything went ok, error otherwise.
     */
    protected function processInlineData($data)
    {
        $exec = true;

        foreach ($data as $source => $content) {
            $css = $content['css'];
            $destination = $content['destination'];

            $css = $this->sass->compile($css);
            $exec = $this->finishCompilation($css, $source, $destination);
        }

        return $exec;
    }

    /**
     * Finish the SASS compilation.
     * The Sass is compiled in the `run()` method because, based on the type of compilation done (whether from a file
     * or directly from the source), the method called is different. This method just group what both type of compilation
     * have in common.
     *
     * @param string $css CSS that was generated
     * @param string $source Path of the source file.
     * @param string $destination Path of the destination file.
     * @return bool
     */
    protected function finishCompilation($css, $source, $destination)
    {
        $destinationDirectory = dirname($destination);

        if (!is_dir($destinationDirectory)) {
            mkdir($destinationDirectory, 0755, true);
        }

        if (!file_put_contents($destination, $css)) {
            return $source;
        } else {
            $this->printTaskSuccess(
                sprintf(
                    'Compiled SASS from <info>%s</info> to <info>%s</info>',
                    $source,
                    $destination
                )
            );
        }

        $this->returnData[$source] = ['css' => $css, 'destination' => $destination];

        return true;
    }

    /**
     * Gets the state from the previous task. Stores it in the `data` attribute of the object.
     * This method is called before the task is run.
     *
     * @param \Robo\State\Data $state State passed from the previous task.
     * @return void
     */
    public function receiveState(Data $state)
    {
        $this->data = $state->getData();
    }
}
