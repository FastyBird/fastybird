The [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) HomeKit Connector is
an extension of the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
ecosystem that enables effortless integration  with [Apple HomeKit](https://en.wikipedia.org/wiki/HomeKit). It provides
users with a simple and user-friendly interface to connect FastyBird devices with [Apple HomeKit](https://en.wikipedia.org/wiki/HomeKit),
allowing easy control of the devices from the Apple Home app. This makes managing and monitoring your devices hassle-free.

# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

A connector is an entity that manages communication with [Apple HomeKit](https://en.wikipedia.org/wiki/HomeKit) system.
It needs to be configured for a specific interface.

## Device

A device is an entity that represents a virtual [Apple HomeKit](https://en.wikipedia.org/wiki/HomeKit) device.

## Device Service

A service is an entity that refers to a specific functionality or feature that a device provides. For example,
a thermostat device might provide a "temperature control" service and a "humidity control" service.

## Device Service Characteristic

A characteristic is an entity that refers to the individual attribute of a service that can be queried or manipulated.
Characteristic represent specific data point that describe the state of a device or allow control over it.
Examples of characteristic include temperature, humidity, on/off status, and brightness level.

# Configuration

To [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem devices
with [Apple HomeKit](https://en.wikipedia.org/wiki/HomeKit), you will need to configure at least one connector.
The connector can be configured using the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
user interface or through the console.
