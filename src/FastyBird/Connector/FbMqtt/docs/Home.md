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

## Configuring the Connectors and Devices through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:fb-mqtt-connector:initialize
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will show you basic menu. To navigate in menu you could write value displayed in square brackets or you
could use arrows to select one of the options:

```shell
FB MQTT connector - installer
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

### Create device

According to [FastyBird MQTT Convention](https://github.com/FastyBird/mqtt-convention) protocol definition, devices
could be configured automatically, because they have to provide its description.

However, you can create a basic configuration for the devices manually.

After new connector is created you will be asked if you want to create new device:

```shell
 Would you like to configure connector device(s)? (yes/no) [yes]:
 > 
```

Or you could choose to manage connector devices from the main menu.

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

## Configuring the Connector with the FastyBird User Interface

You can also configure the FastyBird MQTT connector using the [FastyBird](https://www.fastybird.com)
[IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information on how to do this, please refer
to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) documentation.
