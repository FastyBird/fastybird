<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

# FastyBird IoT Sonoff NS Panel Pro connector

[![Build Status](https://img.shields.io/github/actions/workflow/status/FastyBird/ns-panel-connector/ci.yaml?style=flat-square)](https://github.com/FastyBird/ns-panel-connector/actions)
[![Licence](https://img.shields.io/github/license/FastyBird/ns-panel-connector?style=flat-square)](https://github.com/FastyBird/ns-panel-connector/blob/main/LICENSE.md)
[![Code coverage](https://img.shields.io/coverallsCoverage/github/FastyBird/ns-panel-connector?style=flat-square)](https://coveralls.io/r/FastyBird/ns-panel-connector)
[![Mutation testing](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FFastyBird%2Fns-panel-connector%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/FastyBird/ns-panel-connector/main)

![PHP](https://badgen.net/packagist/php/FastyBird/ns-panel-connector?cache=300&style=flat-square)
[![Latest stable](https://badgen.net/packagist/v/FastyBird/ns-panel-connector/latest?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/ns-panel-connector)
[![Downloads total](https://badgen.net/packagist/dt/FastyBird/ns-panel-connector?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/ns-panel-connector)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

***

## What is Sonoff NS Panel PRO connector?

Sonoff NS Panel PRO connector is extension for [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
which is integrating [Sonoff](https://sonoff.tech) NS Panel PRO human interface panel.

### Features:

- Support for Sonoff NS Panel sub-devices, allowing users to connect and control a wide range of Sonoff devices
- Support for third-party devices management which could be controlled through NS Panels
- Ability to map multiple devices into a single NS Panel device
- Sonoff NS Panel Pro Connector management for the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) [devices module](https://github.com/FastyBird/devices-module), allowing users to easily manage and monitor Sonoff NS Panels
- Advanced device management features, such as controlling power status, measuring energy consumption, and reading sensor data
- [{JSON:API}](https://jsonapi.org/) schemas for full API access, providing a standardized and consistent way for developers to access and manipulate NS Panel device data
- Regular updates with new features and bug fixes, ensuring that the Sonoff NS Panel Pro Connector is always up-to-date and reliable.

Sonoff NS Panel PRO Connector is a distributed extension that is developed in [PHP](https://www.php.net), built on the [Nette](https://nette.org) and [Symfony](https://symfony.com) frameworks,
and is licensed under [Apache2](http://www.apache.org/licenses/LICENSE-2.0).

## Requirements

Sonoff NS Panel PRO connector is tested against PHP 8.1 and require installed [Process Control](https://www.php.net/manual/en/book.pcntl.php) PHP extension.

## Installation

This extension is part of the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem and is installed by default.
In case you want to create you own distribution of [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem you could install this extension with  [Composer](http://getcomposer.org/):

```sh
composer require fastybird/ns-panel-connector
```

## Documentation

Learn how to connect Sonoff NS Panels and manage them with [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system
in [documentation](https://github.com/FastyBird/ns-panel-connector/wiki).

## Feedback

Use the [issue tracker](https://github.com/FastyBird/fastybird/issues) for bugs
or [mail](mailto:code@fastybird.com) or [Tweet](https://twitter.com/fastybird) us for any idea that can improve the
project.

Thank you for testing, reporting and contributing.

## Changelog

For release info check [release page](https://github.com/FastyBird/fastybird/releases).

## Contribute

The sources of this package are contained in the [FastyBird monorepo](https://github.com/FastyBird/fastybird). We welcome contributions for this package on [FastyBird/fastybird](https://github.com/FastyBird/).

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
repository [https://github.com/fastybird/ns-panel-connector](https://github.com/fastybird/ns-panel-connector).
