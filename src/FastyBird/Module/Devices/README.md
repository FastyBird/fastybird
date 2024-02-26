<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

# FastyBird IoT devices module

[![Build Status](https://flat.badgen.net/github/checks/FastyBird/devices-module/main?cache=300&style=flat-square)](https://github.com/FastyBird/devices-module/actions)
[![Licence](https://flat.badgen.net/github/license/FastyBird/devices-module?cache=300&style=flat-square)](https://github.com/FastyBird/devices-module/blob/main/LICENSE.md)
[![Code coverage](https://flat.badgen.net/coveralls/c/github/FastyBird/devices-module?cache=300&style=flat-square)](https://coveralls.io/r/FastyBird/devices-module)
[![Mutation testing](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FFastyBird%2Fdevices-module%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/FastyBird/devices-module/main)

![PHP](https://flat.badgen.net/packagist/php/FastyBird/devices-module?cache=300&style=flat-square)
[![Latest stable](https://flat.badgen.net/packagist/v/FastyBird/devices-module/latest?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/devices-module)
[![Downloads total](https://flat.badgen.net/packagist/dt/FastyBird/devices-module?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/devices-module)
[![PHPStan](https://flat.badgen.net/static/PHPStan/enabled/green?cache=300&style=flat-square)](https://github.com/phpstan/phpstan)

![JS](https://flat.badgen.net/static/js/es6/blue?cache=300&style=flat-square)
[![JS latest stable](https://flat.badgen.net/npm/v/@fastybird/devices-module?cache=300&style=flat-square)](https://www.npmjs.com/package/@fastybird/devices-module)
[![JS downloads total](https://flat.badgen.net/npm/dt/@fastybird/devices-module?cache=300&style=flat-square)](https://www.npmjs.com/package/@fastybird/devices-module)
![Types](https://flat.badgen.net/npm/types/@fastybird/devices-module?cache=300&style=flat-square)

***

## What is FastyBird IoT devices module?

Devices module is a [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
extension for managing connectors and connected devices and their basic logic.

### Features:

- Devices connectors management
- Devices and their channels management
- [{JSON:API}](https://jsonapi.org/) schemas for full api access
- User access [check & validation](https://github.com/FastyBird/simple-auth)
- Multilingual
- User interface integration via [Vue 3](https://vuejs.org) components
- Integrated connector worker for PHP based connectors

[FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) devices module is
an [Apache2 licensed](http://www.apache.org/licenses/LICENSE-2.0) distributed extension, developed
in [PHP](https://www.php.net) on top of the [Nette framework](https://nette.org) and [Symfony framework](https://symfony.com) and in [Typescript](https://www.typescriptlang.org) on top of the [Vue framework](https://vuejs.org).

## Requirements

PHP part of [FastyBird](https://www.fastybird.com) devices module is tested against PHP 8.2 and require installed [BCMath Arbitrary Precision Mathematics](https://www.php.net/manual/en/book.bc.php) and [Process Control](https://www.php.net/manual/en/book.pcntl.php)
PHP extensions.

JavaScript part of [FastyBird](https://www.fastybird.com) devices module is tested
against [ECMAScript 6](https://www.w3schools.com/JS/js_es6.asp)

## Installation

This extension is part of the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem and is installed by default.
In case you want to create you own distribution of [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem you could install this extension with  [Composer](http://getcomposer.org/):

```sh
composer require fastybird/devices-module
```

And for user interface is the best way to install **@fastybird/devices-module** with [Yarn](https://yarnpkg.com/):

```sh
yarn add @fastybird/devices-module
```

or if you prefer npm:

```sh
npm install @fastybird/devices-module
```

## Documentation

:book: Learn how to configure and use devices module and manage your connectors & devices
in [documentation](https://github.com/FastyBird/devices-module/wiki).

# FastyBird

<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/fastybird_row.svg?raw=true" alt="FastyBird"/>
</p>

FastyBird is an Open Source IOT solution built from decoupled components with powerful API and the highest quality code. Read more on [fastybird.com.com](https://www.fastybird.com).

## Documentation

:book: Documentation is available on [docs.fastybird.com](https://docs.fastybird.com).

## Contributing

The sources of this package are contained in the [FastyBird monorepo](https://github.com/FastyBird/fastybird). We welcome
contributions for this package on [FastyBird/fastybird](https://github.com/FastyBird/).

## Feedback

Use the [issue tracker](https://github.com/FastyBird/fastybird/issues) for bugs reporting or send an [mail](mailto:code@fastybird.com)
to us or you could reach us on [X newtwork](https://x.com/fastybird) for any idea that can improve the project.

Thank you for testing, reporting and contributing.

## Changelog

For release info check [release page](https://github.com/FastyBird/fastybird/releases).

## Maintainers

<table>
	<tbody>
		<tr>
			<td align="center">
				<a href="https://github.com/akadlec">
					<img alt="akadlec" width="80" height="80" src="https://avatars3.githubusercontent.com/u/1866672?s=460&amp;v=4" />
				</a>
				<br>
				<a href="https://github.com/akadlec">Adam Kadlec</a>
			</td>
		</tr>
	</tbody>
</table>

***
Homepage [https://www.fastybird.com](https://www.fastybird.com) and
repository [https://github.com/fastybird/devices-module](https://github.com/fastybird/devices-module).
