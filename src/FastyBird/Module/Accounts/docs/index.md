# Getting started

This module adds support for managing [FastyBird](https://www.fastybird.com) IoT accounts, roles and access management.

***

## Installation

The best way to install **fastybird/accounts-module** is using [Composer](https://getcomposer.org/).

> If you don't have Composer yet, [download it](https://getcomposer.org/download/) following the instructions.

### Create new project

If you don't have a project created yet you could start with Nette base project.

You could create new project with simple composer command.

```sh
composer create-project nette/web-project path/to/install
```

Everything required will be then installed in the provided folder.

```sh
cd path/to/install
```

### Install module

Module could be added to your project with composer command:

```sh
composer require fastybird/accounts-module
```

### Module user interface

The best way to install **@fastybird/accounts-module** is using [Yarn](https://yarnpkg.com/):

```sh
yarn add @fastybird/accounts-module
```

or if you prefer npm:

```sh
npm install @fastybird/accounts-module
```

## Configuration

This module is dependent on other Nette extensions. All this extensions have to enabled and configured in NEON
configuration file.

Example configuration could be
found [here](https://github.com/FastyBird/accounts-module/blob/main/config/example.neon)

## Initialization

This module is using database, and need some initial data to be inserted into it.

```sh
your-console-entrypoint fb:accounts-module:initialize
```

This console command is interactive and will ask for all required information.

## Running module interface

This module is dependent on [fastybird/web-server](https://github.com/FastyBird/web-server) which is server-less web
server for serving API content. This module is registering its routes to this webserver automatically. All what you have
to do is start this server with your console entrypoint:

```sh
your-console-entrypoint fb:web-server:start
```

After successful start, server is listening for incoming http api request messages from clients.
