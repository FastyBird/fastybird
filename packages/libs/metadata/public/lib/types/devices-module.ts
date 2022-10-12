import { ButtonPayload, DataType, SwitchPayload } from '@/lib/types/types'

export enum PropertyType {
  DYNAMIC = 'dynamic',
  VARIABLE = 'variable',
  MAPPED = 'mapped',
}

export enum ConnectionState {
  CONNECTED = 'connected',
  DISCONNECTED = 'disconnected',
  INIT = 'init',
  READY = 'ready',
  RUNNING = 'running',
  SLEEPING = 'sleeping',
  STOPPED = 'stopped',
  LOST = 'lost',
  ALERT = 'alert',
  UNKNOWN = 'unknown',
}

export enum DeviceModel {
  CUSTOM = 'custom',

  SONOFF_BASIC = 'sonoff_basic',
  SONOFF_RF = 'sonoff_rf',
  SONOFF_TH = 'sonoff_th',
  SONOFF_SV = 'sonoff_sv',
  SONOFF_SLAMPHER = 'sonoff_slampher',
  SONOFF_S20 = 'sonoff_s20',
  SONOFF_TOUCH = 'sonoff_touch',
  SONOFF_POW = 'sonoff_pow',
  SONOFF_POW_R2 = 'sonoff_pow_r2',
  SONOFF_DUAL = 'sonoff_dual',
  SONOFF_DUAL_R2 = 'sonoff_dual_r2',
  SONOFF_4CH = 'sonoff_4ch',
  SONOFF_4CH_PRO = 'sonoff_4ch_pro',
  SONOFF_RF_BRIDGE = 'sonoff_rf_bridge',
  SONOFF_B1 = 'sonoff_b1',
  SONOFF_LED = 'sonoff_led',
  SONOFF_T1_1CH = 'sonoff_t1_1ch',
  SONOFF_T1_2CH = 'sonoff_t1_2ch',
  SONOFF_T1_3CH = 'sonoff_t1_3ch',
  SONOFF_S31 = 'sonoff_s31',
  SONOFF_SC = 'sonoff_sc',
  SONOFF_SC_PRO = 'sonoff_sc_pro',
  SONOFF_PS_15 = 'sonoff_ps_15',

  AI_THINKER_AI_LIGHT = 'ai_thinker_ai_light',

  FASTYBIRD_WIFI_GW = 'fastybird_wifi_gw',
  FASTYBIRD_3CH_POWER_STRIP_R1 = 'fastybird_3ch_power_strip_r1',
  FASTYBIRD_8CH_BUTTONS = '8ch_buttons',
  FASTYBIRD_16CH_BUTTONS = '16ch_buttons',
}

export enum FirmwareManufacturer {
  GENERIC = 'generic',
  FASTYBIRD = 'fastybird',
  ITEAD = 'itead',
  SHELLY = 'shelly',
  TUYA = 'tuya',
  SONOFF = 'sonoff',
}

export enum HardwareManufacturer {
  GENERIC = 'generic',
  FASTYBIRD = 'fastybird',
  ITEAD = 'itead',
  AI_THINKER = 'ai_thinker',
  SHELLY = 'shelly',
  TUYA = 'tuya',
  SONOFF = 'sonoff',
}

export enum DevicePropertyIdentifier {
  STATE = 'state',
  BATTERY = 'battery',
  WIFI = 'wifi',
  SIGNAL = 'signal',
  SSID = 'ssid',
  RSSI = 'rssi',
  VCC = 'vcc',
  CPU_LOAD = 'cpu_load',
  UPTIME = 'uptime',
  ADDRESS = 'address',
  IP_ADDRESS = 'ip_address',
  STATUS_LED = 'status_led',
  FREE_HEAP = 'free_heap',
}

export enum DeviceAttributeIdentifier {
  HARDWARE_MANUFACTURER = "hardware_manufacturer",
  HARDWARE_MODEL = "hardware_model",
  HARDWARE_VERSION = "hardware_version",
  HARDWARE_MAC_ADDRESS = "hardware_mac_address",
  FIRMWARE_MANUFACTURER = "firmware_manufacturer",
  FIRMWARE_NAME = "firmware_name",
  FIRMWARE_VERSION = "firmware_version",
}

export enum ConnectorPropertyIdentifier {
  STATE = 'state',
  SERVER = 'server',
  PORT = 'port',
  SECURED_PORT = 'secured_port',
  BAUD_RATE = 'baud_rate',
  INTERFACE = 'interface',
  ADDRESS = 'address',
}

export interface ConnectorEntity {
  id: string
  type: string
  identifier: string
  name: string
  comment: string | null
  enabled: boolean
  owner: string | null
}

export interface ConnectorPropertyEntity {
  id: string
  type: PropertyType
  identifier: string
  name: string | null
  settable: boolean
  queryable: boolean
  data_type: DataType
  unit: string | null
  format: string[] | ((string | null)[])[] | (number | null)[] | null
  invalid: string | number | null
  number_of_decimals: number | null
  value?: string | number | boolean | null
  actual_value?: string | number | boolean | ButtonPayload | SwitchPayload | null
  expected_value?: string | number | boolean | ButtonPayload | SwitchPayload | null
  previous_value?: string | number | boolean | ButtonPayload | SwitchPayload | null
  pending?: boolean
  connector: string
  owner: string | null
}

export interface ConnectorControlEntity {
  id: string
  name: string
  connector: string
  owner: string | null
}

export interface DeviceEntity {
  id: string
  type: string
  identifier: string
  name: string | null
  comment: string | null
  connector: string
  parents: string[]
  children: string[]
  owner: string | null
}

export interface DeviceAttributeEntity {
  id: string
  identifier: string
  name: string | null
  content: string | null
  device: string
  owner: string | null
}

export interface DevicePropertyEntity {
  id: string
  type: PropertyType
  identifier: string
  name: string | null
  settable: boolean
  queryable: boolean
  data_type: DataType
  unit: string | null
  format: string[] | ((string | null)[])[] | (number | null)[] | null
  invalid: string | number | null
  number_of_decimals: number | null
  value?: string | number | boolean | null
  actual_value?: string | number | boolean | ButtonPayload | SwitchPayload | null
  expected_value?: string | number | boolean | ButtonPayload | SwitchPayload | null
  previous_value?: string | number | boolean | ButtonPayload | SwitchPayload | null
  pending?: boolean
  device: string
  children: string[]
  owner: string | null
}

export interface DeviceControlEntity {
  id: string
  name: string
  device: string
  owner: string | null
}

export interface ChannelEntity {
  id: string
  identifier: string
  name: string | null
  comment: string | null
  device: string
  owner: string | null
}

export interface ChannelPropertyEntity {
  id: string
  type: PropertyType
  identifier: string
  name: string | null
  settable: boolean
  queryable: boolean
  data_type: DataType
  unit: string | null
  format: string[] | ((string | null)[])[] | (number | null)[] | null
  invalid: string | number | null
  number_of_decimals: number | null
  value?: string | number | boolean | null
  actual_value?: string | number | boolean | ButtonPayload | SwitchPayload | null
  expected_value?: string | number | boolean | ButtonPayload | SwitchPayload | null
  previous_value?: string | number | boolean | ButtonPayload | SwitchPayload | null
  pending?: boolean
  channel: string
  children: string[]
  owner: string | null
}

export interface ChannelControlEntity {
  id: string
  name: string
  channel: string
  owner: string | null
}
