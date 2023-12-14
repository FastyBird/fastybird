<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

The [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) Tuya Connector is an extension for the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem that enables seamless integration
with [Tuya](https://www.tuya.com) devices. It allows users to easily connect and control [Tuya](https://www.tuya.com) devices from within the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem,
providing a simple and user-friendly interface for managing and monitoring your devices.

# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

A connector is an entity that manages communication with [Tuya](https://www.tuya.com) devices. It needs to be configured for a specific device interface.

## Device

A device is an entity that represents a physical [Tuya](https://www.tuya.com) device.

## DPS - Data Points

The Tuya devices transmit information, referred to as "data points" (DPS) or "device function points," in a JSON string format.
These DPS attributes determine the state of the device. The keys within the DPS dictionary correspond to key-value pairs,
where the key is the DP ID and its value is the dpValue.

# Configuration

To use [Tuya](https://www.tuya.com) devices with the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem, you will need to configure at least one connector.
The connector can be configured using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface or through the console.

There are two types of connectors available for selection:

- **Local** - This connector uses the local network for communication.
- **Cloud** - This connector communicates with the [Tuya](https://www.tuya.com) cloud instance.

## Setting Up Tuya Devices

Before using the Tuya connector, you will need to pair your Tuya devices with the Tuya cloud platform and mobile app.
Refer to your device's manual for instructions on how to do this.

## Obtaining Tuya Access Credentials

In order to use the Tuya connector, you will need to obtain certain access credentials.
These can be obtained by following these steps:

### Creating a Tuya Developer Account

To get started, go to [iot.tuya.com](https://iot.tuya.com) and either create a new account or log in if you already have one. Note that this
account is different from the account you use for the Tuya mobile app.

![Login to Tuya cloud platform](https://github.com/FastyBird/tuya-connector/blob/main/docs/_media/tuya_login.png)

>**NOTE:**
Tuya frequently updates their portal and the process for obtaining credentials may change.
If you have trouble following these instructions, please create an issue or pull request with screenshots so we can update them.

### Creating New Cloud Project

From side menu select cloud platform:

![Tuya platform menu](https://github.com/FastyBird/tuya-connector/blob/main/docs/_media/tuya_select_cloud.png)

And now click on "Create Cloud Project" button:

![New project creation](https://github.com/FastyBird/tuya-connector/blob/main/docs/_media/tuya_create_project_btn.png)

Fill in you new project details a do not forget to select all Data Centers you are using in your Tuya application:

![New project creation](https://github.com/FastyBird/tuya-connector/blob/main/docs/_media/tuya_create_project.png)

Now you have to authorize basic Tuya services. This services enables OpenApi for you cloud project and this API
interface will be used by Tuya connector to get devices details.

![Project wizard](https://github.com/FastyBird/tuya-connector/blob/main/docs/_media/tuya_project_wizard.png)

On the overview page you could find your **Access ID** and **Access Secret**. Note these credentials, you will use them later.

![Tuya project credentials](https://github.com/FastyBird/tuya-connector/blob/main/docs/_media/tuya_credentials.png)

### Connecting your Tuya Application

Open **Devices** tab and then open **Link Tuya App Account** and click on **Add App Account**

![Account assign](https://github.com/FastyBird/tuya-connector/blob/main/docs/_media/tuya_cloud_projects.png)

Scan given QR code with you Tuya application, and follow steps shown in you application. When application authorization
is finished, you will see your devices in the Devices list.

![Application code](https://github.com/FastyBird/tuya-connector/blob/main/docs/_media/tuya_scan_code.png)

After your devices are linked with your Tuya cloud account you are able to get Tuya user identifier.

![User identifier](https://github.com/FastyBird/tuya-connector/blob/main/docs/_media/tuya_cloud_user_id.png)

## Configuring the Connectors and Devices through the Console

To configure the connector through the console, run the following command:

```shell
php bin/fb-console fb:tuya-connector:initialize
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will ask you to confirm that you want to continue with the configuration.

```shell
Tuya connector - installer
==========================

 ! [NOTE] This action will create|update|delete connector configuration                                                 

 What would you like to do? [Nothing]:
  [0] Create connector
  [1] Edit connector
  [2] Delete connector
  [3] Manage connector
  [4] List connectors
  [5] Nothing
```

### Create connector

If you choose to create a new connector, you will be asked to choose the mode in which the connector will communicate with the devices:

```shell
 In what mode should this connector communicate with Tuya devices? [Tuya cloud server mode]:
  [0] Local network mode
  [1] Tuya cloud server mode
 > 0
```

You will then be asked to provide a connector identifier and name:

```shell
 Provide connector identifier:
 > my-tuya
```

```shell
 Provide connector name:
 > My Tuya
```

In the following steps, you will need to input your **Access ID** and **Access Secret**, which you obtained previously.

```shell
 Provide cloud authentication Access ID:
 > q3ctsrwvcqdprx5kjtm7
```

```shell
 Provide cloud authentication Access Secret:
 > dc098fa182774e1ca292e76328f8beb6
```

```shell
 Provide which cloud data center you are using? [Central Europe]:
  [0] Central Europe
  [1] Western Europe
  [2] Western America
  [3] Eastern America
  [4] China
  [5] India
 > 0
```

After providing the necessary information, your new [Tuya](https://www.tuya.com) connector will be ready for use.

```shell
 [OK] New connector "My Tuya" was successfully created                                                                
```

### Connectors and Devices management

With this console command you could manage all your connectors and their devices. Just use the main menu to navigate to proper action.

## Configuring the Connector with the FastyBird User Interface

You can also configure the [Tuya](https://www.tuya.com) connector using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface. For more information on how to do this,
please refer to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) documentation.

# Devices Discovery

The [Tuya](https://www.tuya.com) connector includes a built-in feature for automatic device discovery. This feature can be triggered manually
through a console command or from the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface.

## Manual Console Command

To manually trigger device discovery, use the following command:

```shell
php bin/fb-console fb:tuya-connector:discover
```

> **NOTE:**
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will prompt for confirmation before proceeding with the discovery process.

```shell
Tuya connector - discovery
============================

 ! [NOTE] This action will run connector devices discovery.

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to select the connector to use for the discovery process.

```shell
 Would you like to discover devices with "My Tuya" connector (yes/no) [no]:
 > y
```

The connector will then begin searching for new [Tuya](https://www.tuya.com) devices, which may take a few minutes to complete. Once finished,
a list of found devices will be displayed.

```shell
 [INFO] Starting Tuya connector discovery...

[============================] 100% 36 secs/36 secs %

 [INFO] Found 2 new devices


+----+--------------------------------------+----------------------------------------+---------------+--------------+
| #  | ID                                   | Name                                   | Model         | IP address   |
+----+--------------------------------------+----------------------------------------+---------------+--------------+
| 1  | eebbc85d-76e0-4597-883d-8a8f93e7cd54 | Filament bulb                          | N/A           | N/A          |
| 2  | 366e4c13-34c5-4bfe-ac5c-383157f5bd10 | WiFi 2Gang Dimmer Module               | 105b          | 10.10.10.130 |
+----+--------------------------------------+----------------------------------------+---------------+--------------+
 [OK] Devices discovery was successfully finished
```

Now that all newly discovered devices have been found, they are available in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system and can be utilized.

# Known Issues and Limitations

## Mapping DPS to Properties

When using local network mode for device discovery, the connector may not always be able to accurately detect the data
types of each device's DPS (data points). In such cases, it may be necessary to manually edit the device's properties
and correct their configurations.
