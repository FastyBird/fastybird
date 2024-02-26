<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

# FastyBird Redis DB plugin

[![Build Status](https://flat.badgen.net/github/checks/FastyBird/redisdb-plugin/main?cache=300&style=flat-square)](https://github.com/FastyBird/redisdb-plugin/actions)
[![Licence](https://flat.badgen.net/github/license/FastyBird/redisdb-plugin?cache=300&style=flat-square)](https://github.com/FastyBird/redisdb-plugin/blob/main/LICENSE.md)
[![Code coverage](https://flat.badgen.net/coveralls/c/github/FastyBird/redisdb-plugin?cache=300&style=flat-square)](https://coveralls.io/r/FastyBird/redisdb-plugin)
[![Mutation testing](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FFastyBird%2Fredisdb-plugin%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/FastyBird/redisdb-plugin/main)

![PHP](https://flat.badgen.net/packagist/php/FastyBird/redisdb-plugin?cache=300&style=flat-square)
[![Latest stable](https://flat.badgen.net/packagist/v/FastyBird/redisdb-plugin/latest?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/redisdb-plugin)
[![Downloads total](https://flat.badgen.net/packagist/dt/FastyBird/redisdb-plugin?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/redisdb-plugin)
[![PHPStan](https://flat.badgen.net/static/PHPStan/enabled/green?cache=300&style=flat-square)](https://github.com/phpstan/phpstan)

***

## What is Redis DB plugin?

Redis DB plugin is extension for [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
which is implementing [Redis](https://redis.io) Publish & Subscribe data exchange used by other modules of [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
ecosystem and also a states manager for reading and storing application states.

### Features

- Synchronous and asynchronous application state management for reading and storing states in Redis database
- Synchronous and asynchronous exchange for data communication
- Redis clients with basic CRUD operations

Redis DB plugin is an [Apache2 licensed](http://www.apache.org/licenses/LICENSE-2.0) distributed extension, developed
in [PHP](https://www.php.net) on top of the [Nette framework](https://nette.org) and [Symfony framework](https://symfony.com).

## Requirements

Application library is tested against PHP 8.2.

## Installation

This extension is part of the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem and is installed by default.
In case you want to create you own distribution of [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem you could install this extension with  [Composer](http://getcomposer.org/):

```sh
composer require fastybird/redisdb-plugin
```

## Documentation

:book: Learn how to read & write states and consume & publish messages in [documentation](https://github.com/FastyBird/redisdb-plugin/wiki).

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
repository [https://github.com/FastyBird/redisdb-plugin](https://github.com/FastyBird/redisdb-plugin).
