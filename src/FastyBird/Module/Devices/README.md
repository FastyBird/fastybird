# FastyBird IoT devices module

[![Build Status](https://badgen.net/github/checks/FastyBird/devices-module/main?cache=300&style=flat-square)](https://github.com/FastyBird/devices-module/actions)
[![Licence](https://badgen.net/github/license/FastyBird/devices-module?cache=300&style=flat-square)](https://github.com/FastyBird/devices-module/blob/main/LICENSE.md)
[![Code coverage](https://badgen.net/coveralls/c/github/FastyBird/devices-module?cache=300&style=flat-square)](https://coveralls.io/r/FastyBird/devices-module)
[![Mutation testing](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FFastyBird%2Fdevices-module%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/FastyBird/devices-module/main)

![PHP](https://badgen.net/packagist/php/FastyBird/devices-module?cache=300&style=flat-square)
[![PHP latest stable](https://badgen.net/packagist/v/FastyBird/devices-module/latest?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/devices-module)
[![PHP downloads total](https://badgen.net/packagist/dt/FastyBird/devices-module?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/devices-module)
[![PHPStan](https://img.shields.io/badge/phpstan-enabled-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

![JS](https://img.shields.io/badge/js-es6-blue.svg?style=flat-square)
[![JS latest stable](https://badgen.net/npm/v/@fastybird/devices-module?cache=300&style=flat-square)](https://www.npmjs.com/package/@fastybird/devices-module)
[![JS downloads total](https://badgen.net/npm/dt/@fastybird/devices-module?cache=300&style=flat-square)](https://www.npmjs.com/package/@fastybird/devices-module)
![Types](https://badgen.net/npm/types/@fastybird/devices-module?cache=300&style=flat-square)

***

## What is FastyBird IoT devices module?

Devices module is a [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
extension for managing connectors and connected devices and their basic logic.

[FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) devices module is
an [Apache2 licensed](http://www.apache.org/licenses/LICENSE-2.0) distributed extension, developed
in [PHP](https://www.php.net) on top of the [Nette framework](https://nette.org) and [Symfony framework](https://symfony.com) and in [Typescript](https://www.typescriptlang.org) on top of the [Vue framework](https://vuejs.org).

### Features:

- Devices connectors management
- Devices and their channels management
- [{JSON:API}](https://jsonapi.org/) schemas for full api access
- User access [check & validation](https://github.com/FastyBird/simple-auth)
- Multilingual
- User interface integration via [Vue 3](https://vuejs.org) components
- Integrated connector worker for PHP based connectors

## Requirements


PHP part of [FastyBird](https://www.fastybird.com) devices module is tested against PHP 8.1 and require installed [BCMath Arbitrary Precision Mathematics](https://www.php.net/manual/en/book.bc.php) and [Process Control](https://www.php.net/manual/en/book.pcntl.php)
PHP extensions.

JavaScript part of [FastyBird](https://www.fastybird.com) devices module is tested
against [ECMAScript 6](https://www.w3schools.com/JS/js_es6.asp)

## Installation

The best way to install **fastybird/devices-module** is using [Composer](http://getcomposer.org/):

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

Learn how to configure and use devices module and manage your connectors & devices
in [documentation](https://github.com/FastyBird/devices-module/blob/main/.docs/en/index.md).

## Feedback

Use the [issue tracker](https://github.com/FastyBird/fastybird/issues) for bugs
or [mail](mailto:code@fastybird.com) or [Tweet](https://twitter.com/fastybird) us for any idea that can improve the
project.

Thank you for testing, reporting and contributing.

## Changelog

For release info check [release page](https://github.com/FastyBird/fastybird/releases)

## Contribute

The sources of this package are contained in the [FastyBird monorepo](https://github.com/FastyBird/fastybird). We welcome contributions for this package on [FastyBird/fastybird](https://github.com/FastyBird/).

## Maintainers

<table>
	<tbody>
		<tr>
			<td align="center">
				<a href="https://github.com/akadlec">
					<img width="80" height="80" src="https://avatars3.githubusercontent.com/u/1866672?s=460&amp;v=4">
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
