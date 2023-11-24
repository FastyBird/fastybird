<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

# FastyBird IoT Tuya connector

[![Build Status](https://img.shields.io/github/actions/workflow/status/FastyBird/tuya-connector/ci.yaml?style=flat-square)](https://github.com/FastyBird/tuya-connector/actions)
[![Licence](https://img.shields.io/github/license/FastyBird/tuya-connector?style=flat-square)](https://github.com/FastyBird/tuya-connector/blob/main/LICENSE.md)
[![Code coverage](https://img.shields.io/coverallsCoverage/github/FastyBird/tuya-connector?style=flat-square)](https://coveralls.io/r/FastyBird/tuya-connector)
[![Mutation testing](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FFastyBird%2Ftuya-connector%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/FastyBird/tuya-connector/main)

![PHP](https://badgen.net/packagist/php/FastyBird/tuya-connector?cache=300&style=flat-square)
[![Latest stable](https://badgen.net/packagist/v/FastyBird/tuya-connector/latest?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/tuya-connector)
[![Downloads total](https://badgen.net/packagist/dt/FastyBird/tuya-connector?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/tuya-connector)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

***

## What is Tuya connector?

Tuya connector is extension for [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
which is integrating [Tuya](https://www.tuya.com) devices.

### Features:

- The Tuya Connector offers support for both local and cloud-based communication with Tuya devices, providing users with a versatile and flexible way to connect and control a wide range of Tuya devices in their home or office.
- Automated device discovery feature, which automatically detects and adds Tuya devices to the FastyBird ecosystem
- Tuya Connector management for the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) [devices module](https://github.com/FastyBird/devices-module), allowing users to easily manage and monitor Tuya devices
- Advanced device management features, such as controlling power status, measuring energy consumption, and reading sensor data
- [{JSON:API}](https://jsonapi.org/) schemas for full API access, providing a standardized and consistent way for developers to access and manipulate Tuya device data
- Regular updates with new features and bug fixes, ensuring that the Tuya Connector is always up-to-date and reliable.

Tuya Connector is a distributed extension that is developed in [PHP](https://www.php.net), built on the [Nette](https://nette.org) and [Symfony](https://symfony.com) frameworks,
and is licensed under [Apache2](http://www.apache.org/licenses/LICENSE-2.0).

## Requirements

Tuya connector is tested against PHP 8.1 and require installed [Process Control](https://www.php.net/manual/en/book.pcntl.php)
PHP extension.

## Installation

This extension is part of the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem and is installed by default.
In case you want to create you own distribution of [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem you could install this extension with  [Composer](http://getcomposer.org/):

```sh
composer require fastybird/tuya-connector
```

## Documentation

Learn how to connect Tuya devices and manage them with [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system
in [documentation](https://github.com/FastyBird/tuya-connector/wiki).

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
repository [https://github.com/fastybird/tuya-connector](https://github.com/fastybird/tuya-connector).
