# Configuration

To use [Shelly](https://shelly.cloud) devices with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem, you will need to configure at least one connector.
The connector can be configured using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface or through the console.

There are three types of connectors available for selection:

- **Local** - This connector uses the local network for communication and supports the HTTP API, CoIoT, and web sockets.
- **Cloud** - This connector communicates with the [Shelly](https://shelly.cloud) cloud instance.
- **MQTT** - This connector utilizes the MQTT protocol to communicate with an MQTT broker.

## Configuring the Connectors and Devices through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:shelly-connector:install
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

This command is interactive and easy to operate.

The console will show you basic menu. To navigate in menu you could write value displayed in square brackets or you
could use arrows to select one of the options:

```
Shelly connector - installer
============================

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

If you choose to create a new connector, you will be asked to choose the mode in which the connector will communicate with the devices:

```
 In what mode should this connector communicate with Shelly devices? [Local network mode]:
  [0] Local network mode
 > 0
```

You will then be asked to provide a connector identifier and name:

```
 Provide connector identifier:
 > my-shelly
```

```
 Provide connector name:
 > My Shelly
```

> [!NOTE]
If you choose the cloud or MQTT broker mode, you will be prompted to answer additional questions.

After providing the necessary information, your new [Shelly](https://shelly.cloud) connector will be ready for use.

```
 [OK] New connector "My Shelly" was successfully created
```

### Connectors and Devices management

With this console command you could manage all your connectors and their devices. Just use the main menu to navigate to proper action.

## Configuring the Connector with the FastyBird User Interface

You can also configure the [Shelly](https://shelly.cloud) connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information on how to do this,
please refer to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) documentation.
