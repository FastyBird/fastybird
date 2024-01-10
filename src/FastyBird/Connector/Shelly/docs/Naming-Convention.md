# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

A connector entity in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding basic configuration
and is responsible for managing communication with [Shelly](https://shelly.cloud) devices and other [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem services.

## Device

A device in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is representing a physical [Shelly](https://shelly.cloud) device.

## Channel

A channel in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is representing a logical part of a physical
[Shelly](https://shelly.cloud) device e.g. button, relay state, light state.

## Property

A property in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding configuration values or
device actual state of a device. Connector, Device and Channel entity has own Property entities.

### Connector Property

Connector related properties are used to store configuration like `communication mode`. This configuration values are used
to connect to [Shelly](https://shelly.cloud) devices.

### Device Property

Device related properties are used to store configuration like `ip address`, `communication port` or to store basic device information
like `hardware model`, `manufacturer` or `access credential`. Some of them have to be configured to be able to use this connector
or to communicate with device. In case some of the mandatory property is missing, connector will log and error.

### Channel Property

Channel related properties are used for storing actual state of [Shelly](https://shelly.cloud) device. It could be a switch `state` or a light `brightness`.
These values are read from device and stored in system.

## Device Generation

There are two generations of devices supported by this connector.

The **First generation** is based on the ES8266 processor and supports HTTP API, CoIoT API (over UDP), and MQTT.
The **Second generation** is based on the newer ESP32 processor and supports HTTP RPC API, websockets, and MQTT.
