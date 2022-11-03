# Quick start

The purpose of this extension is to prepare basic bootstrap for you application with some default useful extensions.

***

## Installation

The best way to install **fastybird/bootstrap** is using [Composer](http://getcomposer.org/):

```sh
composer require fastybird/bootstrap
```

## Configure bootstrap

This extension is configured via **env** variables. Env variables are used to define all necessary folders for you
application:

```
FB_APP_DIR - is application root dir. Default value is folder where is your composer.json

FB_RESOURCES_DIR - is dir for additional resources. Default value is FB_APP_DIR . '/resources'

FB_TEMP_DIR - is dir for storing temporary data like filecache, generated DI etc. Default value is FB_APP_DIR . '/var/temp'

FB_LOGS_DIR - is dir for application logs or exceptions. Default value is FB_APP_DIR . '/var/logs'

FB_CONFIG_DIR - is dir where you sould place your custom configuration. Default value is FB_APP_DIR . '/config' 
```

From this env variables are created PHP constants sou you could use them in you app.

Values of **FB_APP_DIR**, **FB_TEMP_DIR** and **FB_LOGS_DIR** are also injected into nenon configuration parser, so
could be used to configure your services

```neon
services: 
    -
        type: Your\CoolApp\Service
        arguments: [
            temp: %tempDir%/cache
            logs: %logsDir%/service.result.log
            root: %appDir%
        ]
```

### Overriding parameters

Your app could be configured via `parameters` section in your configuration neon file, but in case you don't want to
store you sensitive data in file you could use configuration via env variables.

Bootstrap will search for all env variables prefixed with `FB_APP_PARAMETER_` and create parameters array from them.
Also structuring parameters is supported, just use delimiter `_`:

```nenon
parameters:
    database:
        password: secretPass
```

is equivalent to:

```php
$_ENV[FB_APP_PARAMETER_DATABASE_PASSWORD] = 'secretPass';
```

### Custom application configuration

Application configuration is done via neon files and this files have to be places into **FB_CONFIG_DIR**.

Bootstrap will automatically load configuration files, all what you have to do is follow naming convention for neon
files:

```
common.neon - file for configuring extension, services, etc.

defaults.neon - file for placing all you parameters

local.neon - file for additional configuration or user specific
```

**common.neon** and **defaults.neon** should be versioned in you application repository, **local.neon** is meant to be
custom local file not be stored in repository.

## Create application container

Now when you have your application configured you could move to next step, creating application entrypoint which will
loads DI and fire `Nette\Application\Application::run`

You can copy & paste it to your project, for example to `<app_root>/www/index.php`.

```php
<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

exit(FastyBird\Bootstrap\Boot\Bootstrap::boot()
    ->createContainer()
    ->getByType(Nette\Application\Application::class)
    ->run());
```

When a call `FastyBird\Bootstrap\Boot\Bootstrap::boot()` is made, bootstrap will try to configure application and
prepare everything for building container.

## Default extensions

This extension has preconfigured some useful extensions.

### Console support

Is implemented via [contribute/console](https://github.com/contributte/console) package. Console entrypoint could be
found in composer `bin` folder and to run command just run:

```sh
vendor/bin/fb_console your:command
```

### Application logger

Is implemented via [contribute/monolog](https://github.com/contributte/monolog) package. And is configured to log all
actions with severity errors and higher.

You could configure optional output of this logger to standard output or into rotating file:

```neon
parameters:
    logger:
        rotatingFile: your.filename.log
        stdOut: true
```

Logger severity level could be also configured via neon extension configuration:

```neon
parameters:
    logger:
        level: 400 # Levels: DEBUG = 100, INFO = 200, NOTICE = 250, WARNING = 300, ERROR = 400, CRITICAL = 500, ALERT = 550, EMERGENCY = 600
```

### Sentry bug tracking

If you would like to track your bug in [Sentry](https://sentry.io/), all what you have to do, is to configure you Sentry
DSN

```neon
parameters:
    sentry:
        dsn: yourSecretSentryDSNstring
```

***
Homepage [https://www.fastybird.com](https://www.fastybird.com) and
repository [https://github.com/FastyBird/bootstrap](https://github.com/FastyBird/bootstrap).
