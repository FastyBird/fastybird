# Exposed Capabilities

Each device which is connected to [Zigbee2MQTT](https://www.zigbee2mqtt.io) service is exposing its capabilities. Each capability
is representing input or output of the device.

**Xiaomi MiJia temperature & humidity sensor (WSDCGQ01LM)**

```json
{
  "temperature": 27.34,
  "humidity": 44.72
}
```

For example Xiaomi sensor is exposing `temperature` and `humidity` capability.

## Device Exposed Capabilities Description

Exposed capabilities are published in device description message which is published in `zigbee2mqtt/bridge/devices` topic.

```json
[
  {
    "date_code": "",
    "definition": {
      "description": "Temperature & humidity sensor",
      "exposes": [
        {
          "access": 1,
          "label": "Temperature",
          "name": "temperature",
          "property": "temperature",
          "type": "numeric"
        },
        {
          "access": 1,
          "label": "Humidity",
          "name": "humidity",
          "property": "humidity",
          "type": "numeric"
        }
      ],
      "model": "WSDCGQ01LM",
      "options": [],
      "supports_ota": false,
      "vendor": "Xiaomi"
    },
    "disabled": false,
    "endpoints": {},
    "friendly_name": "0xa4c138f06eafa3da",
    "ieee_address": "0xa4c138f06eafa3da",
    "interview_completed": true,
    "interviewing": false,
    "manufacturer": "...",
    "model_id": "...",
    "network_address": 37167,
    "power_source": "Battery",
    "supported": true,
    "type": "EndDevice"
  }
]
```

So every device is providing basic information about supported capabilities where you could find capability name, description and data type.

All supported data types are available in [Zigbee2MQTT](https://www.zigbee2mqtt.io) [documentation](https://www.zigbee2mqtt.io/guide/usage/exposes.html)

## Mapping Capabilities

To handle the capabilities exposed by devices, they need to be associated with entities that can be comprehended
by the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem.

The [Zigbee2MQTT](https://www.zigbee2mqtt.io) service has two groups of capabilities: *Generic* and *Specific*

### Generic Capabilities mapping

In generic capabilities group could be found basic data types like *booleans*, *numbers*, *strings* and *enums* and
one special *composite* which combines multiple generic capabilities.

> [!NOTE]
Zigbee2MQTT service in generic group also support special data type **List**, but this data type is not supported for now.

Structure of the generic capability configuration is simple:

```json
{
    "type": "binary",
    "name": "occupancy",
    "label": "Occupancy",
    "property": "occupancy",
    "value_on": true,
    "value_off": false,
    "access": 1
}
```

Within the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem,
*Channels* and *Properties* entities are utilized, where the Property entity stores state values. Hence, this capability
must be linked to these entities.

At first, we need to create Channel entity identifier. This identifier is composed of capability type and property:

```
\FastyBird\Module\Devices\Entities\Channels\Channel(
    identifier: binnary_occupancy
    name: Occupancy
)
```

And next have to be mapped Channel *Dynamic Property* which will then hold device capability state value. The identifier
is just property value:

```
\FastyBird\Module\Devices\Entities\Channels\Properties\Dynamic(
    identifier: occupancy
    name: Occupancy
)
```

In summary, we can express that a single *Capability* corresponds to one *Channel{ and one *Dynamic Property*. But there is
one special case which is related to *Composite* capability type.

```json
{
    "type": "composite",
    "name": "color_xy",
    "label": "Color xy",
    "access": 2,
    "property": "color",
    "features": [
        {
            "type": "numeric",
            "name": "x",
            "label": "X",
            "property": "x",
            "access": 7
        },
        {
            "type": "numeric",
            "name": "y",
            "label": "Y",
            "property": "y",
            "access": 7
        }
    ]
}
```

As you can see, composite capability type is composed of more generic capability types. In this case one *Channel* will
have more *Dynamic Properties*

```
\FastyBird\Module\Devices\Entities\Channels\Channel(
    identifier: composite_color
    name: Color xy
)
```

```
\FastyBird\Module\Devices\Entities\Channels\Properties\Dynamic(
    identifier: x
    name: X
)
\FastyBird\Module\Devices\Entities\Channels\Properties\Dynamic(
    identifier: y
    name: Y
)
```

### Specific Capabilities mapping

Specific capabilities are a little bit tricky, because it has more inner levels and missing "name" on first level.

```json
{
    "type": "light",
    "features": [
        {
            "type": "binary",
            "name": "state",
            "label": "State",
            "property": "state",
            "value_on": "ON",
            "value_off": "OFF",
            "value_toggle": "TOGGLE",
            "access": 7
        },
        {
            "type": "numeric",
            "name": "brightness",
            "label": "Brightness",
            "property": "brightness",
            "value_min": 0,
            "value_max": 254,
            "access": 7
        },
        {
            "type": "composite",
            "name": "color_hs",
            "label": "Color hs",
            "property": "color",
            "features": [
                {
                    "type": "numeric",
                    "name": "hue",
                    "label": "Hue",
                    "property": "hue",
                    "access": 7
                },
                {
                    "type": "numeric",
                    "name": "saturation",
                    "label": "Saturation",
                    "property": "saturation",
                    "access": 7
                }
            ]
        }
    ]
}
```

So in this case we extend mapping to *Channel* by adding info about special capability type:

```
\FastyBird\Module\Devices\Entities\Channels\Channel(
    identifier: light_binary_state
    name: State
)
\FastyBird\Module\Devices\Entities\Channels\Channel(
    identifier: light_numeric_brightness
    name: Brightness
)
\FastyBird\Module\Devices\Entities\Channels\Channel(
    identifier: light_composite_color
    name: Color hs
)
```

As you can see on the example above, this special capability is mapped to 3 *Channel* entities. With this approach we
could easily find *Channel* and *Property* during handling state messages.

After mapping capability to channels we could now map capabilities to properties which will then hold state values.

For the first channel:

```
\FastyBird\Module\Devices\Entities\Channels\Properties\Dynamic(
    identifier: state
    name: State
)
```

For the second channel:

```
\FastyBird\Module\Devices\Entities\Channels\Properties\Dynamic(
    identifier: brightness
    name: Brightness
)
```

And for the third channel:

```
\FastyBird\Module\Devices\Entities\Channels\Properties\Dynamic(
    identifier: hue
    name: Hue
)
\FastyBird\Module\Devices\Entities\Channels\Properties\Dynamic(
    identifier: saturation
    name: Saturation
)
```

## Summary

In short, we could say that *Channel* entity identifier format is: `TYPE_PROPERTY` or `TYPE_SUB-TYPE_PROPERTY` for
specific capability and for the *Property* entity format is just `PROPERTY`.

Generic capability and specific capability with generic capability has mapping:

```
1:1:1 -> Capability : Channel : Property
```

Composite generic capability and specific capability with composite generic capability has mapping:

```
1:1:n -> Capability : Channel : N Properties
```