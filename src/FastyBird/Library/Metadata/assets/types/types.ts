export enum ModulePrefix {
	MODULE_ACCOUNTS = 'accounts-module',
	MODULE_DEVICES = 'devices-module',
	MODULE_TRIGGERS = 'triggers-module',
	MODULE_UI = 'ui-module',
}

export enum ModuleSource {
	NOT_SPECIFIED = '*',
	MODULE_ACCOUNTS = 'com.fastybird.accounts-module',
	MODULE_DEVICES = 'com.fastybird.devices-module',
	MODULE_TRIGGERS = 'com.fastybird.triggers-module',
	MODULE_UI = 'com.fastybird.ui-module',
}

export enum PluginSource {
	NOT_SPECIFIED = '*',
	COUCHDB_STORAGE_PLUGIN = 'com.fastybird.couchdb-storage-plugin',
	RABBITMQ_PLUGIN = 'com.fastybird.rabbitmq-plugin',
	REDISDB_PLUGIN = 'com.fastybird.redisdb-plugin',
	WS_EXCHANGE_PLUGIN = 'com.fastybird.ws-server-plugin',
	WEB_SERVER_PLUGIN = 'com.fastybird.web-server-plugin',
	API_KEY_PLUGIN = 'com.fastybird.api-key-plugin',
}

export enum ConnectorSource {
	NOT_SPECIFIED = '*',
	FB_BUS_CONNECTOR = 'com.fastybird.fb-bus-connector',
	FB_MQTT_CONNECTOR = 'com.fastybird.fb-mqtt-connector',
	SHELLY_CONNECTOR = 'com.fastybird.shelly-connector',
	TUYA_CONNECTOR = 'com.fastybird.tuya-connector',
	SONOFF_CONNECTOR = 'com.fastybird.sonoff-connector',
	MODBUS_CONNECTOR = 'com.fastybird.modbus-connector',
	HOMEKIT_CONNECTOR = 'com.fastybird.homekit-connector',
	VIRTUAL_CONNECTOR = 'com.fastybird.virtual-connector',
	TERMINAL_CONNECTOR = 'com.fastybird.terminal-connector',
	VIERA_CONNECTOR = 'com.fastybird.viera-connector',
	NS_PANEL_CONNECTOR = 'com.fastybird.ns-panel-connector',
	ZIGBEE2MQTT_CONNECTOR = 'com.fastybird.zigbee2mqtt-connector',
}

export enum AutomatorSource {
	NOT_SPECIFIED = '*',
	AUTOMATOR_DEVICE_MODULE = 'com.fastybird.device-module-automator',
	AUTOMATOR_DATE_TIME = 'com.fastybird.date-time-automator',
}

export enum DataType {
	CHAR = 'char',
	UCHAR = 'uchar',
	SHORT = 'short',
	USHORT = 'ushort',
	INT = 'int',
	UINT = 'uint',
	FLOAT = 'float',
	BOOLEAN = 'bool',
	STRING = 'string',
	ENUM = 'enum',
	DATE = 'date',
	TIME = 'time',
	DATETIME = 'datetime',
	COLOR = 'color',
	BUTTON = 'button',
	SWITCH = 'switch',
	UNKNOWN = 'unknown',
}

export enum ShortDataType {
	CHAR = 'i8',
	UCHAR = 'u8',
	SHORT = 'i16',
	USHORT = 'u16',
	INT = 'i32',
	UINT = 'u32',
	FLOAT = 'f',
	BOOLEAN = 'b',
	STRING = 's',
	ENUM = 'e',
	DATE = 'd',
	TIME = 't',
	DATETIME = 'dt',
}

export enum SwitchPayload {
	ON = 'switch_on',
	OFF = 'switch_off',
	TOGGLE = 'switch_toggle',
}

export enum CoverPayload {
	OPEN = 'cover_open',
	OPENING = 'cover_opening',
	OPENED = 'cover_opened',
	CLOSE = 'cover_close',
	CLOSING = 'cover_closing',
	CLOSED = 'cover_closed',
	STOP = 'cover_stop',
	STOPPED = 'cover_stopped',
	CALIBRATE = 'cover_calibrate',
	CALIBRATING = 'cover_calibrating',
}

export enum ButtonPayload {
	PRESSED = 'btn_pressed',
	RELEASED = 'btn_released',
	CLICKED = 'btn_clicked',
	DOUBLE_CLICKED = 'btn_double_clicked',
	TRIPLE_CLICKED = 'btn_triple_clicked',
	LONG_CLICKED = 'btn_long_clicked',
	EXTRA_LONG_CLICKED = 'btn_extra_long_clicked',
}

export enum ControlName {
	CONFIGURE = 'configure',
	RESET = 'reset',
	FACTORY_RESET = 'factory_reset',
	REBOOT = 'reboot',
	TRIGGER = 'trigger',
}

export enum PropertyAction {
	SET = 'set',
	GET = 'get',
	REPORT = 'report',
}

export enum ControlAction {
	SET = 'set',
}

export enum ExchangeCommand {
	SET = 'set',
	GET = 'get',
	REPORT = 'report',
}
