# FastyBird IoT triggers module

[![Build Status](https://badgen.net/github/checks/FastyBird/triggers-module/main?cache=300&style=flat-square)](https://github.com/FastyBird/triggers-module/actions)
[![Licence](https://badgen.net/github/license/FastyBird/triggers-module?cache=300&style=flat-square)](https://github.com/FastyBird/triggers-module/blob/main/LICENSE.md)

![PHP](https://badgen.net/packagist/php/FastyBird/triggers-module?cache=300&style=flat-square)
[![PHP code coverage](https://badgen.net/coveralls/c/github/FastyBird/triggers-module?cache=300&style=flat-square)](https://coveralls.io/r/FastyBird/triggers-module)
[![PHP latest stable](https://badgen.net/packagist/v/FastyBird/triggers-module/latest?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/triggers-module)
[![PHP downloads total](https://badgen.net/packagist/dt/FastyBird/triggers-module?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/triggers-module)
[![PHPStan](https://img.shields.io/badge/phpstan-enabled-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

[![JS latest stable](https://badgen.net/npm/v/@fastybird/triggers-module?cache=300&style=flat-square)](https://www.npmjs.com/package/@fastybird/triggers-module)
[![JS downloads total](https://badgen.net/npm/dt/@fastybird/triggers-module?cache=300&style=flat-square)](https://www.npmjs.com/package/@fastybird/triggers-module)
![Types](https://badgen.net/npm/types/@fastybird/triggers-module?cache=300&style=flat-square)

![Python](https://badgen.net/pypi/python/fastybird-triggers-module?cache=300&style=flat-square)
[![Python latest stable](https://badgen.net/pypi/v/fastybird-triggers-module?cache=300&style=flat-square)](https://pypi.org/project/fastybird-triggers-module/)
[![Python downloads month](https://img.shields.io/pypi/dm/fastybird-triggers-module?cache=300&style=flat-square)](https://pypi.org/project/fastybird-triggers-module/)
[![Black](https://img.shields.io/badge/black-enabled-brightgreen.svg?style=flat-square)](https://github.com/psf/black)
[![MyPy](https://img.shields.io/badge/mypy-enabled-brightgreen.svg?style=flat-square)](http://mypy-lang.org)

## What is FastyBird IoT triggers module?

Triggers module is a combined [Nette framework](https://nette.org) extension, [Vuex ORM](https://vuex-orm.org) plugin
and also [Python](https://python.org) module for managing application automation & notifications.

[FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) triggers module is
an [Apache2 licensed](http://www.apache.org/licenses/LICENSE-2.0) distributed extension, developed
in [PHP](https://www.php.net) with [Nette framework](https://nette.org), in [Typescript](https://www.typescriptlang.org)
and also in [Python](https://python.org).

### Features:

- Triggers and their actions and conditions management
- Support for data [exchange bus](https://github.com/FastyBird/exchange)
- [{JSON:API}](https://jsonapi.org/) schemas for full api access
- User access check & validation
- Multilingual
- JS integration via [Vuex ORM](https://vuex-orm.org) plugin
- Python integration via [SQLAlchemy](https://www.sqlalchemy.org)
- Integrated connector worker for Python based connectors & PHP based connectors

## Requirements

PHP part of [FastyBird](https://www.fastybird.com) triggers module is tested against PHP 7.4
and [ReactPHP http](https://github.com/reactphp/http) 0.8 event-driven, streaming plaintext HTTP server
and [Nette framework](https://nette.org/en/) 3.0 PHP framework for real programmers

JavaScript part of [FastyBird](https://www.fastybird.com) triggers module is tested
against [ECMAScript 6](https://www.w3schools.com/JS/js_es6.asp)

Python part of [FastyBird](https://www.fastybird.com) triggers module is tested against [Python 3.7](http://python.org)

## Installation

#### Application backend in PHP

The best way to install **fastybird/triggers-module** is using [Composer](http://getcomposer.org/):

```sh
composer require fastybird/triggers-module
```

#### Application frontend in JS

The best way to install **@fastybird/triggers-module** is using [Yarn](https://yarnpkg.com/):

```sh
yarn add @fastybird/triggers-module
```

or if you prefer npm:

```sh
npm install @fastybird/triggers-module
```

#### Application workers in Python

The best way to install **fastybird-triggers-module** is using [pip](https://pip.pypa.io/):

```sh
pip install fastybird-triggers-module
```

## Documentation

Learn how to use triggers module and manage your triggers
in [documentation](https://github.com/FastyBird/triggers-module/blob/main/.docs/en/index.md).

## Feedback

Use the [issue tracker](https://github.com/FastyBird/triggers-module/issues) for bugs
or [mail](mailto:code@fastybird.com) or [Tweet](https://twitter.com/fastybird) us for any idea that can improve the
project.

Thank you for testing, reporting and contributing.

## Changelog

For release info check [release page](https://github.com/FastyBird/triggers-module/releases)

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
repository [https://github.com/fastybird/triggers-module](https://github.com/fastybird/triggers-module).
