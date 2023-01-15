# FastyBird IoT Shelly connector

The [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) Shelly Connector is an extension for the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem that enables seamless integration
with [Shelly](https://shelly.cloud) devices. It allows users to easily connect and control [Shelly](https://shelly.cloud) devices from within the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem,
providing a simple and user-friendly interface for managing and monitoring your devices.

## Naming Convention

The connector uses the following naming convention for its entities:

### Connector

A connector is an entity that manages communication with [Shelly](https://shelly.cloud) devices. It needs to be configured for a specific device interface.

### Device

A device is an entity that represents a physical [Shelly](https://shelly.cloud) device.

### Device Generation

There are two generations of devices supported by this connector.
The first generation is based on the ES8266 processor and supports HTTP API, CoIoT API (over UDP), and MQTT.
The second generation is based on the newer ESP32 processor and supports HTTP RPC API, websockets, and MQTT.

## Configuration

To use [Shelly](https://shelly.cloud) devices with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem, you will need to configure at least one connector.
The connector can be configured using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface or through the console.

There are three types of connectors available for selection:

- **Local** - This connector uses the local network for communication and supports the HTTP API, CoIoT, and web sockets.
- **Cloud** - This connector communicates with the [Shelly](https://shelly.cloud) cloud instance.
- **MQTT** - This connector utilizes the MQTT protocol to communicate with an MQTT broker.

### Configuring the Connector through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:shelly-connector:initialize
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will ask you to confirm that you want to continue with the configuration.

```shell
Shelly connector - initialization
=================================

 ! [NOTE] This action will create|update connector configuration.                                                       

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to choose an action:

```shell
 What would you like to do?:
  [0] Create new connector configuration
  [1] Edit existing connector configuration
  [2] Delete existing connector configuration
 > 0
```

If you choose to create a new connector, you will be asked to choose the mode in which the connector will communicate with the devices:

```shell
 In what mode should this connector communicate with devices? [Local network mode]:
  [0] Local network mode
  [1] Cloud server mode
  [2] MQTT broker mode
 > 0
```

You will then be asked to provide a connector identifier and name:

```shell
 Provide connector identifier:
 > my-shelly
```

```shell
 Provide connector name:
 > My Shelly
```

> **NOTE:**
If you choose the cloud or MQTT broker mode, you will be prompted to answer additional questions.

After providing the necessary information, your new [Shelly](https://shelly.cloud) connector will be ready for use.

```shell
 [OK] New connector "My Shelly" was successfully created                                                                
```

### Configuring the Connector with the FastyBird User Interface

You can also configure the [Shelly](https://shelly.cloud) connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information on how to do this,
please refer to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) documentation.

## Devices Discovery

The [Shelly](https://shelly.cloud) connector includes a built-in feature for automatic device discovery. This feature can be triggered manually
through a console command or from the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface.

### Manual Console Command

To manually trigger device discovery, use the following command:

```shell
php bin/fb-console fb:shelly-connector:discover
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will prompt for confirmation before proceeding with the discovery process.

```shell
Shelly connector - discovery
============================

 ! [NOTE] This action will run connector devices discovery.

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to select the connector to use for the discovery process.

```shell
 Would you like to discover devices with "My Shelly" connector (yes/no) [no]:
 > y
```

The connector will then begin searching for new [Shelly](https://shelly.cloud) devices, which may take a few minutes to complete. Once finished,
a list of found devices will be displayed.

```shell
 [INFO] Starting Shelly connector discovery...

[============================] 100% 36 secs/36 secs %

 [INFO] Found 2 new devices


+---+--------------------------------------+--------+---------+--------------+
| # | ID                                   | Name   | Type    | IP address   |
+---+--------------------------------------+--------+---------+--------------+
| 1 | 89b1d985-0183-4c05-8d28-69f4acf4128e | 2cc29e | shrgbw2 | 10.10.10.132 |
| 2 | 8f377380-860f-4ac9-a4de-4be73e5ef59a | e48652 | shrgbw2 | 10.10.10.126 |
+---+--------------------------------------+--------+---------+--------------+

 [OK] Devices discovery was successfully finished
```

Now that all newly discovered devices have been found, they are available in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system and can be utilized.

## Troubleshooting

### Discovery Issues

In some cases, [Shelly](https://shelly.cloud) devices may not be discovered. This is usually due to issues with mDNS service. Each [Shelly](https://shelly.cloud) device
sends out multicast information, but some routers or other network components may block this communication.
To resolve this issue, refer to your router's configuration and check if there are any blocks or configurations that may
be blocking mDNS service.

### Incorrect Mapping

The connector will attempt to map [Shelly](https://shelly.cloud) devices to the correct channels and device properties, but there may be cases
where naming issues or incorrect data types occur. These issues can be corrected through the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface.

## Known Issues and Limitations

Battery-powered devices are a special type of device and must be woken up (e.g. by pressing a button) in order for them
to be discovered by the connector.

Currently, generation 2 battery devices are not supported, but we are working to add support for them in the future.
