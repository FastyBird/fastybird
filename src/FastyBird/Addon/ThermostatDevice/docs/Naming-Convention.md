# Naming Convention

The addon uses the following naming convention for its entities:

## Device

A device in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
refers to a preconfigured entity which is representing software thermostat device.

## Channel

This addon is using multiple channel types.

### Configuration

A configuration channel type in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding configuration of
**Thermostat** device.

### Actors

A actors channel type in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is representing a set of physical
devices which are responsible for heating or cooling

### Sensors

A sensors channel type in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is representing a set of physical
devices which are responsible for providing actual measured temperatures, or opening states.

### Preset

A preset channel type in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding configuration of
**Thermostat** device for specific preset like `manual`, `away`, `comfort`, etc.

## Property

A property in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding configuration values or
device actual state. Connector, Device and Channel entity has own Property entities.

### Channel Property

Channel properties typically serve as repositories for storing the current state of a device. For example, a `configuration
channel` may store properties such as `maximum temperature` or `actors channel` could store connection to physical device.

# HVAC Mode

`HVAC` stands for Heating, Ventilation, and Air Conditioning. This virtual thermostat addon is supporting some of the common
HVAC modes like `cooling`, `heating` or `auto`.