# Sub-Devices Discovery

The **Sonoff NS Panel** connector includes a built-in feature for automatic **Sonoff NS Panel** sub-device discovery. This feature can be triggered manually
through a console command or from the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) user interface.

## Manual Console Command

To manually trigger devices discovery, use the following command:

```shell
php bin/fb-console fb:ns-panel-connector:discover
```

> [!NOTE]
The path to the console command may vary depending on your FastyBird application distribution. For more information, refer to the FastyBird documentation.

The console will prompt for confirmation before proceeding with the discovery process.

```
NS Panel connector - devices discovery
======================================

 ! [NOTE] This action will run connector devices discovery

 Would you like to continue? (yes/no) [no]:
 > y
```

You will then be prompted to select the connector to use for the discovery process.

```
 Would you like to discover devices with "My NS Panel connector" connector (yes/no) [no]:
 > y
```

The connector will then begin searching for new **Sonoff NS Panel** sub-devices, which may take a few minutes to complete. Once finished,
a list of found devices will be displayed.

```
 [INFO] Starting NS Panel connector discovery...


[============================] 100% 1 min, 44 secs/1 min, 44 secs


 [INFO] Stopping NS Panel connector discovery...



 [INFO] Found 1 new devices


+---+--------------------------------------+------------------+------+-------------------+
| # | ID                                   | Name             | Type | NS Panel          |
+---+--------------------------------------+------------------+------+-------------------+
| 1 | f9d367bf-e013-4863-84b6-3180bcceadbc | Room temperature | TH01 | Panel Living Room |
+---+--------------------------------------+------------------+------+-------------------+

 [OK] Devices discovery was successfully finished
```

Now that all newly discovered sub-devices have been found, they are available in the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) system and can be utilized.
