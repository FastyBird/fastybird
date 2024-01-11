# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

A connector entity in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding basic configuration
and is responsible for managing communication with physical world and other [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem services.

## Device

In this connector are used two types of devices.

### Bridge

A bridge device type in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding configuration of
[Zigbee2MQTT](https://www.zigbee2mqtt.io) service.

### Sub-Device

A sub-device type in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is representing physical device
that is connected to [Zigbee2MQTT](https://www.zigbee2mqtt.io) service.

## Channel

Chanel is a mapped property to physical device [exposed capability](https://www.zigbee2mqtt.io/guide/usage/exposes.html) entity. So each exposed
device capability is connected to one device channel.

## Property

A property in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding configuration values or
device actual state. Connector, Device and Channel entity has own Property entities.

### Connector Property

Connector related properties are used to store configuration like `server address`, `port` or `credentials`. This configuration
values are used to connect to [MQTT broker](https://en.wikipedia.org/wiki/MQTT).

### Device Property

Device related properties are used to store configuration like `base topic` or to store basic device information
like `hardware model`, `manufacturer`, `ieee address` or `friendly name`. Some of them have to be configured to be able
to use this connector or to communicate with device. In case some of the mandatory property is missing, connector
will log and error.

### Channel Property

Channel properties are used for mapping device [exposed capabilities](https://www.zigbee2mqtt.io/guide/usage/exposes.html).
Each device is exposing at least one capability. Property entity is then holding physical device state value eg: `state`: `on` or `off`

## MQTT

This connector is using [MQTT broker](https://en.wikipedia.org/wiki/MQTT) for communication with [Zigbee2MQTT](https://www.zigbee2mqtt.io) service.
In order to use this connector, you have to have configured and running MQTT broker.