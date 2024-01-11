# Configuration

To integrate [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem devices
with Thermostat Device Addon, you will need to configure at least one device.
The device can be configured using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
user interface or through the console.

## Configuring the Devices, Actors, Sensors and Preset through the Console

To configure the device through the console, run the following command:

```shell
php bin/fb-console fb:thermostat-device-addon:install
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

```
Thermostat Device addon - installer
===================================

 ! [NOTE] This action will create|update|delete addon configuration

 What would you like to do? [Nothing]:
  [0] Create thermostat
  [1] Edit thermostat
  [2] Delete thermostat
  [3] Manage thermostat
  [4] List thermostats
  [5] Nothing
 > 0
```

Thermostat virtual device is here to provide thermostat functions with usage of other devices.

You could use this device in several HVAC modes:

- **Heater** - Used for heating rooms
- **Cooler** - Used for cooling rooms
- **Auto** - Combining both modes to automatically heat or cool rooms

Each thermostat could be configured with presets.

Actually supported preset are: **manual**,  **away**,  **eco**,  **home**,  **comfort**,  **sleep**,  **anti freeze**

And is up to you if you want to use, one, two od all presets.

