# FastyBird Redis DB plugin

[![Build Status](https://badgen.net/github/checks/FastyBird/redisdb-plugin/main?cache=300&style=flat-square)](https://github.com/FastyBird/redisdb-plugin/actions)
[![Licence](https://badgen.net/github/license/FastyBird/redisdb-plugin?cache=300&style=flat-square)](https://github.com/FastyBird/redisdb-plugin/blob/main/LICENSE.md)
[![Code coverage](https://badgen.net/coveralls/c/github/FastyBird/redisdb-plugin?cache=300&style=flat-square)](https://coveralls.io/r/FastyBird/redisdb-plugin)
[![Mutation testing](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FFastyBird%2Fredisdb-plugin%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/FastyBird/redisdb-plugin/main)

![PHP](https://badgen.net/packagist/php/FastyBird/redisdb-plugin?cache=300&style=flat-square)
[![PHP latest stable](https://badgen.net/packagist/v/FastyBird/redisdb-plugin/latest?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/redisdb-plugin)
[![PHP downloads total](https://badgen.net/packagist/dt/FastyBird/redisdb-plugin?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/redisdb-plugin)
[![PHPStan](https://img.shields.io/badge/phpstan-enabled-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

***

## What is Redis DB plugin?

Redis DB plugin is extension for [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
which is implementing [Redis](https://redis.io) PubSub exchange and states manager for reading
and setting application state.

Redis DB plugin is an [Apache2 licensed](http://www.apache.org/licenses/LICENSE-2.0) distributed extension, developed
in [PHP](https://www.php.net) on top of the [Nette framework](https://nette.org) and [Symfony framework](https://symfony.com).

### Features

- Built-in server command for running standalone exchange client
- Application state management for reading and storing states in Redis database
- Implemented sync and async Redis client
- Redis DB pub/sub exchange client

## Installation

The best way to install **fastybird/redisdb-plugin** is using [Composer](http://getcomposer.org/):

```sh
composer require fastybird/redisdb-plugin
```

## Documentation

Learn how to read & write states and consume & publish messages
in [documentation](https://github.com/FastyBird/redisdb-plugin/blob/main/.docs/en/index.md).

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
repository [https://github.com/FastyBird/redisdb-plugin](https://github.com/FastyBird/redisdb-plugin).
