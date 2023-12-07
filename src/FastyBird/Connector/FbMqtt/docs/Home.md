<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

The FastyBird MQTT Connector is an addition to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
ecosystem that facilitates integration with devices using the [FastyBird MQTT Convention](https://github.com/FastyBird/mqtt-convention) protocol.
This enables users to effortlessly connect and control their devices using the [FastyBird MQTT Convention](https://github.com/FastyBird/mqtt-convention)
protocol within the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem,
providing a convenient and intuitive interface for managing and monitoring their devices.

# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

An entity that handles communication with devices using the [FastyBird MQTT Convention](https://github.com/FastyBird/mqtt-convention) is known as a connector.

## Device

A device in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
refers to a physical device that adheres to the [FastyBird MQTT Convention](https://github.com/FastyBird/mqtt-convention).

## Channel

Devices can have multiple separate components, referred to as channels. For instance, a light device might have separate
channels for the switch, color, and light temperature. These channels are logically distinct from each other and can be managed independently.

## Property

A device and channel may possess multiple properties, which reflect the basic attributes of the device or channel.
These properties often take the form of numerical values or specific states. For instance, a thermostat channel may have
a temperature property, while an environment channel may have properties for temperature, humidity, and air quality.
Similarly, a lights channel could have properties for intensity and color.

# Configuration

To connect to devices that use the [FastyBird MQTT Convention](https://github.com/FastyBird/mqtt-convention) protocol
with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem,
you must set up at least one connector. You can configure the connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface or by using the console.

## Configuring the Connector through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:fb-mqtt-connector:initialize
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will ask you to confirm that you want to continue with the configuration.

```shell
FastyBird MQTT connector - initialization
=========================================

 ! [NOTE] This action will create|update|delete connector configuration                                                 

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to choose an action:

```shell
 What would you like to do? [Nothing]:
  [0] Create new connector configuration
  [1] Edit existing connector configuration
  [2] Delete existing connector configuration
  [3] List FB MQTT connectors
  [4] Nothing
 > 0
```

When opting to create a new connector, you'll be prompted to select the protocol version that the connector will use to
communicate with the devices.

```shell
 What type of FB MQTT protocol will this connector handle? [FB MQTT v1 protocol]:
  [0] FB MQTT v1 protocol
 > 0
```

You will then be asked to provide a connector identifier and name:

```shell
 Provide connector identifier:
 > my-fb-mqtt
```

```shell
 Provide connector name:
 > My FastyBird MQTT
```

> **NOTE:**
You will be prompted to provide another communication settings like server address, username, password etc.

After providing the necessary information, your new FastyBird MQTT connector will be ready for use.

```shell
 [OK] New connector "My FastyBird MQTT" was successfully created                                                                
```

## Configuring the Connector with the FastyBird User Interface

You can also configure the FastyBird MQTT connector using the [FastyBird](https://www.fastybird.com)
[IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information on how to do this, please refer
to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) documentation.

# Devices Configuration

According to [FastyBird MQTT Convention](https://github.com/FastyBird/mqtt-convention) protocol definition, devices
could be configured automatically, because they have to provide its description.

However, you can create a basic configuration for the devices either through the user interface or a console command.

## Manual Console Command

To manually trigger device discovery, use the following command:

```shell
php bin/fb-console fb:fb-mqtt-connector:devices
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will prompt for confirmation before proceeding with the devices configuration process.

```shell
FastyBird MQTT connector - devices management
=============================================

 ! [NOTE] This action will create|update|delete connector device.                                                       

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to select connector to manage devices.

```shell
 Please select connector under which you want to manage devices:
  [0] my-fb-mqtt [My FastyBird MQTT]
 > 0
```

You will then be prompted to select device management action.

```shell
 What would you like to do?:
  [0] Create new connector configuration
  [1] Edit existing connector configuration
  [2] Delete existing connector configuration
  [3] List FB MQTT connectors
  [4] Nothing
 > 0
```

Now you will be asked to provide some device details:

```shell
 Provide device identifier:
 > first-device
```

```shell
 Provide device name:
 > First device - temperature & humidity
```

And that's it! Devices should provide their description messages for everything else. If everything is correct,
you will receive a confirmation message.

```shell
 [OK] Device register was successfully created
```
