# Configuration

To connect to devices that use the [Zigbee2MQTT](https://www.zigbee2mqtt.io) bridge with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem, you must
set up at least one connector. You can configure the connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface or
by using the console.

## Configuring the Connectors and Devices through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:zigbee2mqtt-connector:install
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

This command is interactive and easy to operate.

The console will show you basic menu. To navigate in menu you could write value displayed in square brackets or you
could use arrows to select one of the options:

```shell
Zigbee2MQTT connector - installer
=================================

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

When opting to create a new connector, you'll be prompted to provide a connector identifier and name:

```shell
 Provide connector identifier:
 > zigbee2mqtt
```

```shell
 Provide connector name:
 > Zigbee2MQTT Integration
```

Next questions are related to connection to MQTT broker. If you are using local MQTT broker with default values, you cloud
use prefilled values.

```shell
 Provide MQTT server address [127.0.0.1]:
 > 
```

```shell
 Provide MQTT server port [1883]:
 > 
```

```shell
 Provide MQTT server secured port [8883]:
 > 
```

```shell
 Provide MQTT server username (leave blank if no username is required):
 > 
```

After providing the necessary information, your new [Zigbee2MQTT](https://www.zigbee2mqtt.io) connector will be ready for use.

```shell
 [OK] New connector "Zigbee2MQTT Integration" was successfully created
```

### Create bridge

According to naming convention, bridge device have to be created and configured with [Zigbee2MQTT](https://www.zigbee2mqtt.io) bridge.

After new connector is created you will be asked if you want to create new device:

```shell
 Would you like to configure connector bridge(s)? (yes/no) [yes]:
 > 
```

Or you could choose to manage connector bridges from the main menu.

Now you will be asked to provide some device details:

```shell
 Provide device name:
 > Zigbee2MQTT Bridge
```

And that's it! One bridge is running, it will handle all hardware devices automatically.

```shell
 [OK] Bridge "Zigbee2MQTT Bridge" was successfully created.
```

## Configuring the Connector with the FastyBird User Interface

You can also configure the [Zigbee2MQTT](https://www.zigbee2mqtt.io) connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information
on how to do this, please refer to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) [documentation](https://docs.fastybird.com).
