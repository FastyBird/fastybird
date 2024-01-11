# Naming Convention

The connector uses the following naming convention for its entities:

## Connector

A connector entity in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding basic configuration
and is responsible for managing communication with devices using the [FastyBird MQTT Convention](https://github.com/FastyBird/mqtt-convention).

## Device

A device in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem
refers to a physical device that adheres to the [FastyBird MQTT Convention](https://github.com/FastyBird/mqtt-convention).

## Channel

Devices can have multiple separate components, referred to as channels. For instance, a light device might have separate
channels for the switch, color, and light temperature. These channels are logically distinct from each other and can be managed independently.

## Property

A property in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem refers to a entity which is holding configuration values or
device actual state. Connector, Device and Channel entity has own Property entities.

### Connector Property

Connector related properties are used to store configuration like `server address`, `port` or `credentials`. This configuration
values are used to connect to [MQTT broker](https://en.wikipedia.org/wiki/MQTT).

### Device Property

Device related properties are used to store device `state` or to store basic device information
like `hardware model` or `manufacturer`. Some of them have to be configured to be able to use this connector or to communicate
with device. In case some of the mandatory property is missing, connector will log and error.

### Channel Property

Channel properties are used for storing device channels states. Each device is exposing at least one channel.
Property entity is then holding physical device state value eg: `state`: `on` or `off`

## MQTT Broker

A MQTT broker is a service which is used to handle communication between devices and [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) connectors.
You could use self-hosted applications like [Mosquitto](https://mosquitto.org) and also services provided by other vendors like [HideMQ](https://www.hivemq.com) or [CloudMQTT](https://www.cloudmqtt.com) 
