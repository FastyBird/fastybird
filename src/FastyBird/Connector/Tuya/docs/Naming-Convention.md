# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

A connector entity in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding basic configuration
and is responsible for managing communication with [Tuya](https://www.tuya.com) devices and other [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem services.

## Device

A device in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is representing a physical [Tuya](https://www.tuya.com) device.

## Channel

Chanel is a mapped property to physical device data point entity.

## Property

A property in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding configuration values or
device actual state of a device. Connector, Device and Channel entity has own Property entities.

### Connector Property

Connector related properties are used to store configuration like `communication mode`, `access secret` or `uid`. This configuration
values are used to connect to [Tuya](https://www.tuya.com) cloud.

### Device Property

Device related properties are used to store configuration like `ip address`, `communication port` or to store basic device information
like `hardware model`, `manufacturer` or `local key`. Some of them have to be configured to be able to use this connector
or to communicate with device. In case some of the mandatory property is missing, connector will log and error.

### Channel Property

Channel related properties are used for storing actual state of [Tuya](https://www.tuya.com) device. It could be switch `state` or light `brightness`.
These values are read from device and stored in system.

## DPS - Data Points

The [Tuya](https://www.tuya.com) devices transmit information, referred to as "data points" (DPS) or "device function points," in a JSON string format.
These DPS attributes determine the state of the device. The keys within the DPS dictionary correspond to key-value pairs,
where the key is the DP ID and its value is the dpValue.

## Device Mode

There are two devices modes supported by this connector.

The first mode is **Cloud mode** and uses communication with [Tuya](https://www.tuya.com) cloud servers.
The second mode is **Local mode** and is supported by [Tuya](https://www.tuya.com) devices. It allows you to control device
through local API.
