# FastyBird WS exchange plugin

[![Build Status](https://badgen.net/github/checks/FastyBird/ws-exchange-plugin/main?cache=300&style=flat-square)](https://github.com/FastyBird/ws-exchange-plugin/actions)
[![Licence](https://badgen.net/github/license/FastyBird/ws-exchange-plugin?cache=300&style=flat-square)](https://github.com/FastyBird/ws-exchange-plugin/blob/main/LICENSE.md)
[![Code coverage](https://badgen.net/coveralls/c/github/FastyBird/ws-exchange-plugin?cache=300&style=flat-square)](https://coveralls.io/r/FastyBird/ws-exchange-plugin)
[![Mutation testing](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FFastyBird%2Fws-exchange-plugin%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/FastyBird/ws-exchange-plugin/main)

![PHP](https://badgen.net/packagist/php/FastyBird/ws-exchange-plugin?cache=300&style=flat-square)
[![PHP latest stable](https://badgen.net/packagist/v/FastyBird/ws-exchange-plugin/latest?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/ws-exchange-plugin)
[![PHP downloads total](https://badgen.net/packagist/dt/FastyBird/ws-exchange-plugin?cache=300&style=flat-square)](https://packagist.org/packages/FastyBird/ws-exchange-plugin)
[![PHPStan](https://img.shields.io/badge/phpstan-enabled-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

***

## What is WS exchange plugin?

WS exchange plugin is extension for [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
which is implementing websockets [WAMPv1](https://wamp-proto.org) server based on [iPbulikuj WAMP server](https://github.com/ipublikuj/websockets-wamp)
and websockets [WAMPv1](https://wamp-proto.org) client for [Vue 3](https://vuejs.org) based user interface.

WS exchange plugin is an [Apache2 licensed](http://www.apache.org/licenses/LICENSE-2.0) distributed extension, developed
in [PHP](https://www.php.net) on top of the [Nette framework](https://nette.org) and [Symfony framework](https://symfony.com).

### Features

- Built-in server command for running standalone server
- Built-in Vue 3 client plugin
- WAMP v1 pub/sub and RPC implemented via event system
- Simple broadcast publisher

## Installation

The best way to install **fastybird/ws-exchange-plugin** is using [Composer](http://getcomposer.org/):

```sh
composer require fastybird/ws-exchange-plugin
```

And for user interface is the best way to install **@fastybird/ws-exchange-plugin** with [Yarn](https://yarnpkg.com/):

```sh
yarn add @fastybird/ws-exchange-plugin
```

or if you prefer npm:

```sh
npm install @fastybird/ws-exchange-plugin
```

## Documentation

Learn how to connect, consume & call RPC messages in [documentation](https://github.com/FastyBird/ws-exchange-plugin/blob/main/.docs/en/index.md).

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
repository [https://github.com/FastyBird/ws-exchange-plugin](https://github.com/FastyBird/ws-exchange-plugin).
