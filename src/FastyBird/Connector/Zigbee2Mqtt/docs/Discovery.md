# Devices Discovery

The [Zigbee2MQTT](https://www.zigbee2mqtt.io) service has devices discover which could be triggered in its user interface.
But this connector has also supported discovery feature which will trigger devices discovery in [Zigbee2MQTT](https://www.zigbee2mqtt.io) service.

Connector based devices discovery can be triggered manually through a console command or from the
[FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface.

## Manual Console Command

To manually trigger device discovery, use the following command:

```shell
php bin/fb-console fb:zigbee2mqtt-connector:discover
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will prompt for confirmation before proceeding with the discovery process.

```
Zigbee2MQTT connector - devices discovery
=========================================

 ! [NOTE] This action will run connector devices discovery

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to select the connector to use for the discovery process.

```
 Would you like to discover devices with "Zigbee2MQTT Integration" connector (yes/no) [no]:
 > y
```

The connector will then send request to the [Zigbee2MQTT](https://www.zigbee2mqtt.io) service via MQTT message, and you will
be able to connect new Zigbee device in next 100 sec to your coordinator. Once finished, a list of found devices will be displayed.

```
 [INFO] Starting Zigbee2MQTT connector discovery...


[============================] 100% 1 min, 44 secs/1 min, 44 secs


 [INFO] Stopping Zigbee2MQTT connector discovery...



 [INFO] Found 1 new devices


+---+--------------------------------------+---------------+--------+--------------+--------------------+
| # | ID                                   | Name          | Model  | Manufacturer | Bridge             |
+---+--------------------------------------+---------------+--------+--------------+--------------------+
| 1 | 68c0ec14-9c0c-47c0-923e-f015dca26d54 | Motion sensor | TS0202 | TuYa         | Zigbee2MQTT Bridge |
+---+--------------------------------------+---------------+--------+--------------+--------------------+


 [OK] Devices discovery was successfully finished
```

Now that all newly discovered devices have been found, they are available in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system and can be utilized.
