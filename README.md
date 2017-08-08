# Robo Sass

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?branch=master)](LICENSE.txt)
[![Build Status](https://travis-ci.org/elephfront/robo-sass.svg?branch=master)](https://travis-ci.org/elephfront/robo-sass)
[![Codecov](https://img.shields.io/codecov/c/github/elephfront/robo-sass.svg)](https://github.com/elephfront/robo-sass)

This [Robo](https://github.com/consolidation/robo) task performs a compilation of your SASS files.

This task performs the compilation using the [absalomedia/sassphp](https://github.com/absalomedia/sassphp) PHP extension.

## Requirements

- PHP >= 7.1.0
- Robo
- The [absalomedia/sassphp](https://github.com/absalomedia/sassphp) PHP extension. 

## Installation

You can install this Robo task using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require elephfront/robo-sass
```

## Installing the extension

To install the extension, you need to follow the steps below :

```
git clone git://github.com/absalomedia/sassphp`
cd sassphp
git submodule init
git submodule update
php install.php
make test
make install
```

Once the extension has been installed, you need to update your php.ini (or add a new configuration file your php.ini goes looking in to load extra configuration setup) file to install the extension on your PHP setup by adding the following line :

```
extension=sass.so
```

## Using the task

You can load the task in your RoboFile using the `LoadSassTaskTrait` trait:

```php
use Elephfront\RoboSass\Task\Loader\LoadSassTaskTrait;

class RoboFile extends Tasks
{

    use LoadSassTaskTrait;
    
    public function compileSass()
    {
        $this
            ->taskSass([
                'assets/scss/main.scss' => 'assets/min/css/main.min.css',
                'assets/scss/home.scss' => 'assets/min/css/home.min.css',
            ])
            ->run();
    }
}
```

The only argument the `taskSass()` takes is an array (`$destinationsMap`) which maps the source files to the destination files : it will load the **assets/scss/main.scss**, do its magic and put the final content in **assets/min/css/main.min.css** and do the same for all of the other files.

## Chained State support

Robo includes a concept called the [Chained State](http://robo.li/collections/#chained-state) that allows tasks that need to work together to be executed in a sequence and pass the state of the execution of a task to the next one.
For instance, if you are managing assets files, you will have a task that compile SCSS to CSS then another one that minify the results. The first task can pass the state of its work to the next one, without having to call both methods in a separate sequence.

The **robo-sass** task is compatible with this feature.

All you need to do is make the previous task return the content the **robo-sass** task should operate on using the `data` argument of a `Robo\Result::success()` or `Robo\Result::error()` call. The passed `data` should have the following format:
 
```php
$data = [
    'path/to/source/file' => [
        'css' => '// Some (S)CSS code',
        'destination' => 'path/to/destination/file
    ]
];
```

In turn, when the **robo-sass** task is done, it will pass the results of its work to the next task following the same format.

## Preventing the results from being written

By default, the **robo-sass** task writes the result of its work into the destination file(s) passed in the `$destinationsMap` argument. If the **robo-sass** task is not the last one in the sequence, you can disable the file writing using the `disableWriteFile()` method. The files will be processed but the results will not be persisted and only passed to the response :

```php
$this
    ->taskCssMinify([
        'assets/scss/main.css' => 'assets/min/css/main.min.css',
        'assets/scss/home.css' => 'assets/min/css/home.min.css',
    ])
        ->disableWriteFile()
    ->someOtherTask()
    ->run();
```

## Contributing

If you find a bug or would like to ask for a feature, please use the [GitHub issue tracker](https://github.com/Elephfront/robo-sass/issues).
If you would like to submit a fix or a feature, please fork the repository and [submit a pull request](https://github.com/Elephfront/robo-sass/pulls).

### Coding standards

This repository follows the PSR-2 standard. 

## License

Copyright (c) 2017, Yves Piquel and licensed under [The MIT License](http://opensource.org/licenses/mit-license.php).
Please refer to the LICENSE.txt file.
