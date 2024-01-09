# Devices Discovery

The [Tuya](https://www.tuya.com) connector includes a built-in feature for automatic devices discovery. This feature can be triggered manually
through a console command or from the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface.

## Manual Console Command

To manually trigger device discovery, use the following command:

```shell
php bin/fb-console fb:tuya-connector:discover
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will prompt for confirmation before proceeding with the discovery process.

```
Tuya connector - discovery
==========================

 ! [NOTE] This action will run connector devices discovery.

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to select the connector to use for the discovery process.

```
 Would you like to discover devices with "My Tuya" connector (yes/no) [no]:
 > y
```

The connector will then begin searching for new [Tuya](https://www.tuya.com) devices, which may take a few minutes to complete. Once finished,
a list of found devices will be displayed.

```
 [INFO] Starting Tuya connector discovery...


[============================] 100% 1 min, 44 secs/1 min, 44 secs


 [INFO] Stopping Tuya connector discovery...



 [INFO] Found 2 new devices


+----+--------------------------------------+----------------------------------------+---------------+--------------+
| #  | ID                                   | Name                                   | Model         | IP address   |
+----+--------------------------------------+----------------------------------------+---------------+--------------+
| 1  | eebbc85d-76e0-4597-883d-8a8f93e7cd54 | Filament bulb                          | N/A           | N/A          |
| 2  | 366e4c13-34c5-4bfe-ac5c-383157f5bd10 | WiFi 2Gang Dimmer Module               | 105b          | 10.10.10.130 |
+----+--------------------------------------+----------------------------------------+---------------+--------------+


 [OK] Devices discovery was successfully finished
```

Now that all newly discovered devices have been found, they are available in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system and can be utilized.
