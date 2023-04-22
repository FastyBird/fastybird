# Quick start

The purpose of this plugin is to create php based WS server for serving and handling sockets real time connections.

***

## Installation

The best way to install **fastybird/ws-server-plugin** is using [Composer](http://getcomposer.org/):

```sh
composer require fastybird/ws-server-plugin
```

After that, you have to register plugin in *config.neon*.

```neon
extensions:
    fbWsServerPlugin: FastyBird\Plugin\WsServer\DI\WsServerExtension
```

This plugin is dependent on other extensions, and they have to be registered too

```neon
extensions:
    ...
    contributteConsole: Contributte\Console\DI\ConsoleExtension(%consoleMode%)
    ipubWebsockets: IPub\WebSockets\DI\WebSocketsExtension
    ipubWebsocketsWamp: IPub\WebSocketsWAMP\DI\WebSocketsWAMPExtension
```

> For information how to configure these extensions please visit their doc pages

## Configuration

This plugin has some configuration options:

```neon
fbWsServerPlugin:
    access:
        keys: f9657db3-b9e0-4a6d-a482-76a8099edbce
        origins: yourdomain.tld,service.yourdomain.tld
```

Where:

- `access -> keys` are comma separated access keys which will server validate on clients connections
- `access -> origins` are comma separated allowed domain names which will server validate on clients connections

## Running server

This plugin has implemented command interface for running server. All you have to do is just run one command:

```sh
<app_root>/vendor/bin/fb-console fb:ws-server:start
```

***
Homepage [https://www.fastybird.com](https://www.fastybird.com) and
repository [https://github.com/FastyBird/ws-server-pluging](https://github.com/FastyBird/ws-server-plugin).
