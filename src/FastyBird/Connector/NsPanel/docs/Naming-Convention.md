# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

A connector entity in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding basic configuration
and is responsible for managing communication with **Sonoff NS Panel** devices and other [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem services.

## Device

In this connector are used three types of devices.

### Gateway

A gateway device type in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding configuration of
**Sonoff NS Panel** device.

### Sub-Device

A sub-device type in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is representing physical device
that is connected to **Sonoff NS Panel** device via [Zigbee](https://en.wikipedia.org/wiki/Zigbee) network.

> [!NOTE]
Only Zigbee devices are now supported. In future will be supported other types. It depends on Sonoff developers team

### Third-party device

A third-party device in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to an entity that represents virtual device which
is mapped to other device connected to [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) and allow you to be controlled via **Sonoff NS Panel** interface.

## Device Capability

A capability is an entity that refers to a specific functionality or feature that a device provides. For example,
a thermostat device might provide a "temperature control" capability and a "humidity control" capability.

## Device Capability Protocol

A protocol is an entity that refers to the individual attribute of a capability that can be queried or manipulated.
Protocol represent specific data point that describe the state of a device or allow control over it.
Examples of protocol include temperature, humidity, on/off status, and brightness level.

## Device Mode

There are three devices modes supported by this connector.

The first mode is **Gateway mode** and in this mode are NS Panels used only as gateways. In this mode will be used only sub-devices.
The second mode is **Device mode** and in this mode are NS Panels used only to use Third-party devices, so only mapped devices
are connected to NS Panel. Last mode is **Both mode** and this mode is combining both previous modes, so all features are available.
