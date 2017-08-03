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
namespace Elephfront\RoboSass\Task\Loader;

use Elephfront\RoboSass\Task\Sass;

trait LoadSassTaskTrait
{
    
    /**
     * Exposes the ImportJavascript task.
     *
     * @param array $destinationMap Key / value pairs array where the key is the source and the value the destination.
     * @return \Elephfront\RoboSass\Task\Sass Instance of the Sass Task
     */
    protected function taskSass($destinationMap = [])
    {
        return $this->task(Sass::class, $destinationMap);
    }
}