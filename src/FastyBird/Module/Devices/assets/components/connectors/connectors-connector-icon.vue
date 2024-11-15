<template>
	<fb-icon-with-child
		v-if="props.withState"
		:type="stateColor"
		:size="props.size"
		:data-connector-state="stateName"
	>
		<template #primary>
			<fas-ethernet />
		</template>
		<template #secondary>
			<component :is="stateIcon" />
		</template>
	</fb-icon-with-child>

	<el-icon
		v-else
		:size="props.size"
	>
		<fas-ethernet />
	</el-icon>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { ElIcon } from 'element-plus';

import {
	FasEthernet,
	FarCirclePause,
	FarCircleStop,
	FasCircleExclamation,
	FarCircleQuestion,
	FarCircleUser,
	FarCirclePlay,
	FarCircle,
	FarCircleCheck,
} from '@fastybird/web-ui-icons';
import { FbIconWithChild } from '@fastybird/web-ui-library';
import { ConnectionState } from '@fastybird/metadata-library';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';

import { useConnectorState } from '../../composables';
import { StateColor } from '../../types';

import { IConnectorsConnectorIconProps } from './connectors-connector-icon.types';

import type { Component } from 'vue';

defineOptions({
	name: 'ConnectorsConnectorIcon',
});

const props = withDefaults(defineProps<IConnectorsConnectorIconProps>(), {
	withState: false,
});

const { status: wsStatus } = useWampV1Client();

const { state: connectorState } = useConnectorState(props.connector);

const stateIcon = computed<Component>((): Component => {
	if (!wsStatus || connectorState.value === ConnectionState.SLEEPING) {
		return FarCirclePause;
	} else if ([ConnectionState.STOPPED, ConnectionState.DISCONNECTED].includes(connectorState.value)) {
		return FarCircleStop;
	} else if (connectorState.value === ConnectionState.ALERT) {
		return FasCircleExclamation;
	} else if (connectorState.value === ConnectionState.LOST) {
		return FarCircleQuestion;
	} else if (connectorState.value === ConnectionState.INIT) {
		return FarCircleUser;
	} else if ([ConnectionState.RUNNING, ConnectionState.READY].includes(connectorState.value)) {
		return FarCirclePlay;
	} else if (connectorState.value === ConnectionState.CONNECTED) {
		return FarCircleCheck;
	}

	return FarCircle;
});

const stateName = computed<string>((): string => {
	if (!wsStatus || connectorState.value === ConnectionState.SLEEPING) {
		return 'pause';
	} else if ([ConnectionState.STOPPED, ConnectionState.DISCONNECTED].includes(connectorState.value)) {
		return 'stop';
	} else if (connectorState.value === ConnectionState.ALERT) {
		return 'alert';
	} else if (connectorState.value === ConnectionState.LOST) {
		return 'lost';
	} else if (connectorState.value === ConnectionState.INIT) {
		return 'init';
	} else if ([ConnectionState.RUNNING, ConnectionState.READY].includes(connectorState.value)) {
		return 'ready';
	} else if (connectorState.value === ConnectionState.CONNECTED) {
		return 'connected';
	}

	return 'unknown';
});

const stateColor = computed<StateColor>((): StateColor => {
	if (!wsStatus || [ConnectionState.UNKNOWN].includes(connectorState.value)) {
		return undefined;
	}

	if ([ConnectionState.CONNECTED, ConnectionState.READY, ConnectionState.RUNNING].includes(connectorState.value)) {
		return 'success';
	} else if ([ConnectionState.INIT].includes(connectorState.value)) {
		return 'info';
	} else if ([ConnectionState.DISCONNECTED, ConnectionState.STOPPED, ConnectionState.SLEEPING].includes(connectorState.value)) {
		return 'warning';
	}

	return 'danger';
});
</script>
