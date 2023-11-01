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

## Configuring the Connector through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:viera-connector:initialize
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will ask you to confirm that you want to continue with the configuration.

```shell
Virtual connector - initialization
==================================

 ! [NOTE] This action will create|update|delete connector configuration.

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to choose an action:

```shell
 What would you like to do?:
  [0] Create new connector configuration
  [1] Edit existing connector configuration
  [2] Delete existing connector configuration
  [3] List Virtual connectors
  [4] Nothing
 > 0
```

You will then be asked to provide a connector identifier and name:

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

## Configuring the Connector with the FastyBird User Interface

You can also configure the Virtual connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information on how to do this,
please refer to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) documentation.

# Virtual Devices

This connector is here to provide interface for other developers for creating software base devices.
