# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

A connector entity in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
refers to an entity that manages communication with Virtual devices. It needs to be configured for a specific device interface.

## Device

A device is an abstract entity that represents a virtual device. Each virtual device have to extend this basic abstract entity.

### Thermostat

A thermostat type in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
refers to a preconfigured entity which is representing software thermostat device.

## Channel

Chanel is a universal entity which could represent logical part of a virtual device.

## Property

A property in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding configuration values or
device actual state. Connector, Device and Channel entity has own Property entities.

### Connector Property

Connector related properties are used to store connector state.

### Device Property

Device related properties are used to store basic device information like `manufacturer` (could be a developer or company
name), `mac address` or `mode`. What type of value is store in device's property depends on developer.

### Channel Property

Channel properties typically serve as repositories for storing the current state of a device. For example, a thermostat
device may store properties such as temperature and actor state within a channel.