<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

# FastyBird IoT Zigbee2MQTT connector

[![Build Status](https://img.shields.io/github/actions/workflow/status/FastyBird/zigbee2mqtt-connector/ci.yaml?style=flat-square)](https://github.com/FastyBird/zigbee2mqtt-connector/actions)
[![Licence](https://img.shields.io/github/license/FastyBird/zigbee2mqtt-connector?style=flat-square)](https://github.com/FastyBird/zigbee2mqtt-connector/blob/main/LICENSE.md)
[![Code coverage](https://img.shields.io/coverallsCoverage/github/FastyBird/zigbee2mqtt-connector?style=flat-square)](https://coveralls.io/r/FastyBird/zigbee2mqtt-connector)
[![Mutation testing](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FFastyBird%2Fzigbee2mqtt-connector%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/FastyBird/zigbee2mqtt-connector/main)

![PHP](https://badgen.net/packagist/php/FastyBird/zigbee2mqtt-connector?cache=300&style=flat-square)
[![Latest stable](https://badgen.net/packagist/v/FastyBird/zigbee2mqtt-connector/latest?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/zigbee2mqtt-connector)
[![Downloads total](https://badgen.net/packagist/dt/FastyBird/zigbee2mqtt-connector?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/zigbee2mqtt-connector)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

***

## What is Zigbee2MQTT connector?

Zigbee2MQTT connector is extension for [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
which is integrating [Zigbee2MQTT](https://www.zigbee2mqtt.io) bridges.

### Features:

- [Zigbee2MQTT](https://www.zigbee2mqtt.io) devices support with autodiscovery
- Integration with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) [devices module](https://github.com/FastyBird/devices-module) for easy management and monitoring of Zigbee2MQTT devices
- Advanced device management features, such as controlling power status, measuring energy consumption, and reading sensor data
- [{JSON:API}](https://jsonapi.org/) schemas for full API access, providing a standardized and consistent way for developers to access and manipulate Zigbee2MQTT powered device data
- Regular updates with new features and bug fixes, ensuring that the Zigbee2MQTT Connector is always up-to-date and reliable.

Zigbee2MQTT Connector is a distributed extension that is developed in [PHP](https://www.php.net), built on the [Nette](https://nette.org) and [Symfony](https://symfony.com) frameworks,
and is licensed under [Apache2](http://www.apache.org/licenses/LICENSE-2.0).

## Requirements

Zigbee2MQTT connector is tested against PHP 8.1 and require installed [Process Control](https://www.php.net/manual/en/book.pcntl.php)
PHP extension.

## Installation

This extension is part of the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem and is installed by default.
In case you want to create you own distribution of [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem you could install this extension with  [Composer](http://getcomposer.org/):

```sh
composer require fastybird/zigbee2mqtt-connector
```

## Documentation

:book: Learn how to connect FastyBird MQTT devices and manage them with [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system
in [documentation](https://github.com/FastyBird/zigbee2mqtt-connector/wiki).

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
repository [https://github.com/fastybird/zigbee2mqtt-connector](https://github.com/fastybird/zigbee2mqtt-connector).
