The [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) Sonoff NS Panel Connector is
an extension of the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
ecosystem that enables effortless integration  with [Sonoff NS Panels](https://sonoff.tech/product/central-control-panel/nspanel-pro/).

It provides users with a simple and user-friendly interface to connect FastyBird devices with **Sonoff NS Panel**
and also to connect NS Panel devices with [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things),
allowing easy control of the devices from the NS Panels screens and from FastyBird interface. This makes managing and monitoring your devices hassle-free.

# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

A connector is an entity that manages configuration and communication with **Sonoff NS Panels**.
Each NS Panel have to be configured with connector.

## Device

### Sub-Device

A sub-device is an entity which represent a device which is connected to **Sonoff NS Panel** via [Zigbee](https://en.wikipedia.org/wiki/Zigbee) network

> NOTE: Only Zigbee devices ara now supported. In future will be supported other types. It depends on Sonoff developers team

### Third-party device

A third-party device is an entity that represents virtual device which is mapped to other device connected to [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
and allow you to be controlled via **Sonoff NS Panel** interface.

## Device Capability

A capability is an entity that refers to a specific functionality or feature that a device provides. For example,
a thermostat device might provide a "temperature control" capability and a "humidity control" capability.

## Device Capability Protocol

A protocol is an entity that refers to the individual attribute of a capability that can be queried or manipulated.
Protocol represent specific data point that describe the state of a device or allow control over it.
Examples of protocol include temperature, humidity, on/off status, and brightness level.

# Configuration

To integrate [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem devices
with **Sonoff NS Panel**, you will need to configure at least one connector.
The connector can be configured using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
user interface or through the console.

## Configuring the Connector through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:ns-panel-connector:initialize
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

After triggering the command you will get information message:

```shell
NS Panel connector - initialization
===================================

 ! [NOTE] This action will create|update|delete connector configuration                                                 

 Would you like to continue? (yes/no) [no]:
 > y
```

If you are ready to process press **Y** and hit **Enter**. You will then be prompted to choose an action:

```shell
 What would you like to do? [Nothing]:
  [0] Create new connector configuration
  [1] Edit existing connector configuration
  [2] Delete existing connector configuration
  [3] List NS Panel connectors
  [4] Nothing
 > 0
```

If you choose to create a new connector, you will be asked to provide basic connector configuration:

```shell
 In what mode should this connector communicate with NS Panels? [2]:
  [0] Only NS Panel gateway mode
  [1] Only NS Panel third-party devices mode
  [2] Both modes
 > Both modes
```

```shell
 Provide connector identifier:
 > my-ns-panel-connector
```

```shell
 Provide connector name:
 > My NS Panel connector
```

After providing the necessary information, your new **Sonoff NS Panel** connector will be ready for use.

```shell
 [OK] Connector "My NS Panel connector" was successfully created.                                                       
 ```

## Configuring the Connector with the FastyBird User Interface

You can also configure the **Sonoff NS Panel** connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information on how to do this,
please refer to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) documentation.

# Third-party Devices Configuration

With your new connector set up, you could now configure the third-party devices with which the connector will communicate.
This can be accomplished either through a console command or through the user interface of the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things).

## Manual Console Command

To manually trigger device configuration, use the following command:

```shell
php bin/fb-console fb:ns-panel-connector:devices
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will prompt for confirmation before proceeding with the devices configuration process.

```shell
NS Panel connector - devices management
=======================================

 ! [NOTE] This action will manage connector NS Panels and their devices                                                 

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to select connector which you want to manage.

```shell
 Please select connector under which you want to manage devices [my-ns-panel-connector [My NS Panel connector]]:
  [0] my-ns-panel-connector [My NS Panel connector]
 > 0
```

You will then be prompted to select connector management action.

```shell
 What would you like to do? [Nothing]:
  [0] Connect new NS Panel
  [1] Edit existing NS Panel
  [2] Delete existing NS Panel
  [3] List NS Panels
  [4] Manage NS Panel devices
  [5] Nothing
 > 0
```

### Connect New Sonoff NS Panel

If you would like to connect new **Sonoff NS Panel** to you connector, you have to choose `Connect new NS Panel`

You will be asked to provide basic NS Panel configuration:

```shell
 Provide identifier:
 > panel-living-room
```

```shell
 Provide device name:
 > Living Room Panel
```

You have to get IP address of your panel. You could find it in you **Sonoff NS Panel**. Just open **Sonoff NS Panel** `Settings` go to `About`
and find row `IP`.

```shell
 Provide NS Panel local IP address or domain:
 > 10.10.0.123
 ```

> NOTE: If you enter wrong IP address, command will show you a warning message.

Now you have to prepare your **Sonoff NS Panel** for pairing. On you **Sonoff NS Panel** go to `Settings`
and then to `About` and tap **7** times in a row on `Name`. An information message will popup on your **Sonoff NS Panel** screen.

<img alt="pairing" src="https://github.com/FastyBird/ns-panel-connector/blob/main/docs/_media/allow_access.png" />
 
```shell
 ! [NOTE] Now you have to prepare your NS Panel for pairing. Go to Settings, then to About and tap 7 times in a row on  
 !        Name.                                                                                                         

 Is your NS Panel ready? (yes/no) [no]:
 > y
```

Now you could confirm command to proceed pairing process. If everything goes ok you will get a confirmation message

```shell
 [OK] NS Panel "Panel Living Room" was successfully created.
```

### Create New Third-party device

When you have connected at least one **Sonoff NS Panel** you could create third-party devices and connect them with **Sonoff NS Panel**.

Choose NS Panel devices management:

```shell
  What would you like to do? [Nothing]:
  [0] Connect new NS Panel
  [1] Edit existing NS Panel
  [2] Delete existing NS Panel
  [3] List NS Panels
  [4] Manage NS Panel devices
  [5] Nothing
 > 4
```

You will have to select which connected **Sonoff NS Panel** you want to configure:

```shell
 Please select NS Panel to manage:
  [0] ns-panel-living-room [Panel Obývák]
 > 
```

You will then be prompted to select device management action. To create new device just select `Create new device`

```shell
 What would you like to do? [Nothing]:
  [0] Create new device
  [1] Edit existing device
  [2] Delete existing device
  [3] List devices
  [4] Manage device capabilities
  [5] Nothing
 > 0
```

Now you will be asked to provide some device details:

```shell
 Provide identifier:
 > livin-room-main-lamp
```

```shell
 Provide device name:
 > Living room main lamp
```

You are now required to select a device category, which will determine the specific capabilities and protocols of the device.

```shell
 Please select device category:
  [0] Button
  [1] Contact Sensor
  [2] Curtain
  [3] Light
  [4] Motion Sensor
  [5] Plug
  [6] Smoke Detector
  [7] Switch
  [8] Temperature and Humidity Sensor
  [9] Water Leak Detector
 > 3
```

If there are no errors, you will receive a success message.

```shell
 [OK] Device "Living room main lamp" was successfully created.
```

Each device have to have defined capabilities. So in next steps you will be prompted to configure device's capabilities.

> **NOTE:**
The list of items may vary depending on the device category.

```shell
 What type of device capability you would like to add? [Power]:
  [0] Power
  [1] Brightness
  [2] Color Temperature
 > 1
```

Let's create **Brightness** capability:

```shell
 What type of capability protocol you would like to add? [Brightness]:
  [0] Brightness
 > Brightness
```

These protocols are mandatory and must be configured.

You have two options. Connect protocols with FastyBird device or configure it as static value.

Let's try static configuration value:

```shell
 Connect protocol with device? (yes/no) [yes]:
 > n
```

Some protocols have a defined set of allowed values, while others accept values from a range. Therefore, the next question will vary depending on the selected protocol.

```shell
 Provide protocol value:
 > 50
```

And if you choose to connect characteristic with device:

```shell
 Connect protocol with device? (yes/no) [yes]:
 > y
```

```shell
 Select device for mapping:
  [0] lighting-living-room [Living room lighting]
  [1] floor-heating-livin-room [Living room floor heating]
  [2] window-sensor-lifing-room [Living room window sensor]
 > 0
```

Now you have to choose device channel:

```shell
 Select device channel for mapping:
  [0] main_light [Main Light]
 > 
```

And channel's property:

```shell
 Select channel property for mapping:
  [0] brightness
  [1] temperature
  [2] state
 > 0
```

After all required protocols are configured you will be prompted with question if you want to configure optional protocols.

```shell
 What type of device capability you would like to add? [Color RGB]:
  [0] Color RGB
  [1] None
 > 1
```

The process is same as previous steps.

If there are no errors, you will be back in NS Panel management main menu:

```shell
 What would you like to do? [Nothing]:
  [0] Create new device
  [1] Edit existing device
  [2] Delete existing device
  [3] List devices
  [4] Manage device capabilities
  [5] Nothing
 > 5
```

You could configure as many devices as you want.

# Sub-Devices Discovery

The **Sonoff NS Panel** connector includes a built-in feature for automatic **Sonoff NS Panel** sub-device discovery. This feature can be triggered manually
through a console command or from the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface.

## Manual Console Command

To manually trigger devices discovery, use the following command:

```shell
php bin/fb-console fb:ns-panel-connector:discover
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will prompt for confirmation before proceeding with the discovery process.

```shell
NS Panel connector - devices discovery
======================================

 ! [NOTE] This action will run connector devices discovery                                                              

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to select the connector to use for the discovery process.

```shell
 Would you like to discover devices with "My NS Panel connector" connector (yes/no) [no]:
 > y
```

The connector will then begin searching for new **Sonoff NS Panel** sub-devices, which may take a few minutes to complete. Once finished,
a list of found devices will be displayed.

```shell
 [INFO] Starting NS Panel connector discovery...

[============================] 100% 36 secs/36 secs %

 [INFO] Found 1 new devices


+---+--------------------------------------+------------------+------+-------------------+
| # | ID                                   | Name             | Type | NS Panel          |
+---+--------------------------------------+------------------+------+-------------------+
| 1 | f9d367bf-e013-4863-84b6-3180bcceadbc | Room temperature | TH01 | Panel Living Room |
+---+--------------------------------------+------------------+------+-------------------+

 [OK] Devices discovery was successfully finished
```

Now that all newly discovered sub-devices have been found, they are available in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system and can be utilized.

# Known Issues and Limitations

## Third-Party Devices

The **Sonoff NS Panel** support variety of devices categories but due to some bugs in their system only some of them could be configured.
So only available are listed in interfaces.

Some Capabilities have not necessary Protocols, but they have to be configured to be able to connect devices with the **Sonoff NS Panel**.
In that case you could configure them as not mapped and set fixed value.