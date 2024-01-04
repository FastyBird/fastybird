<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

> [!IMPORTANT]
This documentation is meant to be used by developers or users which has basic programming skills. If you are regular user
please use FastyBird IoT documentation which is available on [docs.fastybird.com](https://docs.fastybird.com). 

The Zigbee2MQTT Connector is an addition to the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem that facilitates integration with
devices using the [Zigbee](https://en.wikipedia.org/wiki/Zigbee) wireless network through the [Zigbee2MQTT](https://www.zigbee2mqtt.io) bridge. This connector enables users to
effortlessly connect and control their devices using the [Zigbee2MQTT](https://www.zigbee2mqtt.io) bridge within the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
ecosystem, providing a convenient and intuitive interface for managing and monitoring their devices.

> [!TIP]
To better understand what some parts of the connector meant to be used for, please refer to the [Naming Convention](Naming-Convention) page.

> [!TIP]
Basic information on how to install and configure this connector could be found in [Configuration](Configuration) page.

> [!TIP]
Physical devices needs to be mapped to [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things)
ecosystem. This is done automatically during discovery process. If you need more info of how it is done, check [Exposes](Exposes) page.

# Troubleshooting

## Incorrect Mapping

The connector will attempt to map [Zigbee2MQTT](https://www.zigbee2mqtt.io) devices and their capabilities to the correct
data types according to received exposed configuration, but there may be cases where incorrect data type is set. These issues
can be corrected through the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface.

# Known Issues and Limitations

Some of the [Zigbee2MQTT](https://www.zigbee2mqtt.io) devices could expose [List type](https://www.zigbee2mqtt.io/guide/usage/exposes.html#list)
capability, but this capability is now not supported by this connector. If you find any device which is using this type of
capability, feel free to open a [feature request](https://github.com/FastyBird/fastybird/issues)
