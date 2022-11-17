# FastyBird IoT Redis DB & WS exchange bridge

[![Build Status](https://badgen.net/github/checks/FastyBird/redisdb-ws-exchange-bridge/master?cache=300&style=flast-square)](https://github.com/FastyBird/redisdb-ws-exchange-bridge/actions)
[![Licence](https://badgen.net/github/license/FastyBird/redisdb-ws-exchange-bridge?cache=300&style=flast-square)](https://github.com/FastyBird/redisdb-ws-exchange-bridge/blob/master/LICENSE.md)
[![Code coverage](https://badgen.net/coveralls/c/github/FastyBird/redisdb-ws-exchange-bridge?cache=300&style=flast-square)](https://coveralls.io/r/FastyBird/redisdb-ws-exchange-bridge)
[![Mutation testing](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FFastyBird%2Fredisdb-ws-exchange-bridge%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/FastyBird/redisdb-ws-exchange-bridge/main)

![PHP](https://badgen.net/packagist/php/FastyBird/redisdb-ws-exchange-bridge?cache=300&style=flast-square)
[![Latest stable](https://badgen.net/packagist/v/FastyBird/redisdb-ws-exchange-bridge/latest?cache=300&style=flast-square)](https://packagist.org/packages/FastyBird/redisdb-ws-exchange-bridge)
[![Downloads total](https://badgen.net/packagist/dt/FastyBird/redisdb-ws-exchange-bridge?cache=300&style=flast-square)](https://packagist.org/packages/FastyBird/redisdb-ws-exchange-bridge)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

***

## What is Redis DB & WS exchange bridge?

Redis DB & WS exchange bridge is extension for [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
which is creating bridge between [Redis DB plugin](https://github.com/FastyBird/redisdb-plugin) and [WS exchange plugin](https://github.com/FastyBird/ws-exchange-plugin).

Redis DB & WS exchange bridge is an [Apache2 licensed](http://www.apache.org/licenses/LICENSE-2.0) distributed extension, developed
in [PHP](https://www.php.net) on top of the [Nette framework](https://nette.org) and [Symfony framework](https://symfony.com).

### Features:

- Redis DB async client exchange subscriber
- WS clients RPC messages transformer to exchange bus
- Exchange bus messages transformer to WS subscribed clients

## Requirements

Redis DB & WS exchange bridge is tested against PHP 8.1.

## Installation

The best way to install **fastybird/redisdb-ws-exchange-bridge** is using [Composer](http://getcomposer.org/):

```sh
composer require fastybird/redisdb-ws-exchange-bridge
```

## Documentation

Learn how to build bridge between Redis DB and WS exchange
in [documentation](https://github.com/FastyBird/redisdb-ws-exchange-bridge/blob/master/.docs/en/index.md).

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

<table>&
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
repository [https://github.com/fastybird/redisdb-ws-exchange-bridge](https://github.com/fastybird/redisdb-ws-exchange-bridge).
