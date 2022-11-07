export const CONNECTOR_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/connector/(?<type>[a-z0-9-]+)$'
export const CONNECTOR_PROPERTIES_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/property/connector/(?<type>[a-z0-9-]+)$'
export const CONNECTOR_CONTROLS_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/control/connector$'

export const DEVICE_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/device/(?<type>[a-z0-9-]+)$'
export const DEVICE_PROPERTIES_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/property/device/(?<type>[a-z0-9-]+)$'
export const DEVICE_CONTROLS_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/control/connector$'

export const CHANNEL_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/channel$'
export const CHANNEL_PROPERTIES_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/property/channel/(?<type>[a-z0-9-]+)$'
export const CHANNEL_CONTROLS_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/control/connector$'

export const ANY_CONTROL_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/control/(?<parent>[a-z0-9-]+)$'
export const ANY_PROPERTY_ENTITY_REG_EXP = '^(?<source>[a-z0-9.-]+)/property/(?<parent>[a-z0-9-]+)/(?<type>[a-z0-9-]+)$'
