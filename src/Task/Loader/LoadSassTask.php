<?php
namespace Elephfront\RoboSass\Task\Loader;

use Elephfront\RoboSass\Task\Sass;

trait LoadSassTask
{
    
    /**
     * Exposes the ImportJavascript task.
     *
     * @param array $destinationMap Key / value pairs array where the key is the source and the value the destination.
     * @return \Elephfront\RoboSass\Task\Loader\LoadSassTask Instance of the Sass Task
     */
    protected function taskSass($destinationMap = [])
    {
        return $this->task(Sass::class, $destinationMap);
    }
}