<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

The [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) Virtual Connector is an extension for the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem that enables seamless integration
of virtual devices. It allows developers to easily create devices which will communicate with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem.

# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

A connector is an entity that manages communication with Virtual devices. It needs to be configured for a specific device interface.

## Device

A device is an entity that represents a virtual device.

# Configuration

To use Virtual devices with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem, you will need to configure at least one connector.
The connector can be configured using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface or through the console.

## Configuring the Connectors and Devices through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:virtual-connector:install
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

After triggering the command you will get information message:

```shell
Virtual connector - installer
=============================

 ! [NOTE] This action will create|update|delete connector configuration

 What would you like to do? [Nothing]:
  [0] Create connector
  [1] Edit connector
  [2] Delete connector
  [3] Manage connector
  [4] List connectors
  [5] Nothing
 > 0
```

### Create connector

If you choose to create a new connector, you will be asked to provide a connector identifier and name:

```shell
 Provide connector identifier:
 > my-virtual
```

```shell
 Provide connector name:
 > My Virtual
```

After providing the necessary information, your new Virtual connector will be ready for use.

```shell
 [OK] New connector "My Virtual" was successfully created
```

### Create device

After new connector is created you will be asked if you want to create new device:

```shell
 Would you like to configure connector device(s)? (yes/no) [yes]:
 > 
```

Or you could choose to manage connector devices from the main menu.

### Connectors and Devices management

With this console command you could manage all your connectors and their devices. Just use the main menu to navigate to proper action.

## Configuring the Connector with the FastyBird User Interface

You can also configure the Virtual connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information on how to do this,
please refer to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) documentation.

# Virtual Devices

This connector is here to provide interface for other developers for creating software base devices.
