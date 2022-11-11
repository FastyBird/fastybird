<template>
	<fb-ui-icon-with-child
		v-if="props.withState"
		:variant="stateColor"
		:data-device-state="stateName"
	>
		<template #main>
			<font-awesome-icon icon="plug" />
		</template>
		<template #child>
			<font-awesome-icon :icon="stateIcon" />
		</template>
	</fb-ui-icon-with-child>

	<template v-else>
		<font-awesome-icon icon="plug" />
	</template>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FbUiIconWithChild, FbUiVariantTypes } from '@fastybird/web-ui-library';
import { ConnectionState } from '@fastybird/metadata-library';
import { useWsExchangeClient } from '@fastybird/ws-exchange-plugin';

import { useDeviceState } from '@/composables';
import { IDevice } from '@/models/types';

interface IDevicesIconProps {
	device: IDevice;
	withState?: boolean;
}

const props = withDefaults(defineProps<IDevicesIconProps>(), {
	withState: false,
});

const { status: wsStatus } = useWsExchangeClient();

const { state: deviceState } = useDeviceState(props.device);

const stateIcon = computed<string>((): string => {
	if (!wsStatus || deviceState.value === ConnectionState.SLEEPING) {
		return 'pause-circle';
	} else if ([ConnectionState.STOPPED, ConnectionState.DISCONNECTED].includes(deviceState.value)) {
		return 'stop-circle';
	} else if (deviceState.value === ConnectionState.ALERT) {
		return 'exclamation-circle';
	} else if (deviceState.value === ConnectionState.LOST) {
		return 'question-circle';
	} else if (deviceState.value === ConnectionState.INIT) {
		return 'user-circle';
	} else if ([ConnectionState.RUNNING, ConnectionState.READY, ConnectionState.CONNECTED].includes(deviceState.value)) {
		return 'play-circle';
	}

	return 'circle';
});

const stateName = computed<string>((): string => {
	if (!wsStatus || deviceState.value === ConnectionState.SLEEPING) {
		return 'pause';
	} else if ([ConnectionState.STOPPED, ConnectionState.DISCONNECTED].includes(deviceState.value)) {
		return 'stop';
	} else if (deviceState.value === ConnectionState.ALERT) {
		return 'alert';
	} else if (deviceState.value === ConnectionState.LOST) {
		return 'lost';
	} else if (deviceState.value === ConnectionState.INIT) {
		return 'init';
	} else if ([ConnectionState.RUNNING, ConnectionState.READY].includes(deviceState.value)) {
		return 'ready';
	} else if (deviceState.value === ConnectionState.CONNECTED) {
		return 'connected';
	}

	return 'unknown';
});

const stateColor = computed<FbUiVariantTypes>((): FbUiVariantTypes => {
	if (!wsStatus || deviceState.value === ConnectionState.SLEEPING) {
		return FbUiVariantTypes.WARNING;
	} else if (deviceState.value === ConnectionState.ALERT) {
		return FbUiVariantTypes.DANGER;
	} else if (deviceState.value === ConnectionState.INIT) {
		return FbUiVariantTypes.INFO;
	} else if ([ConnectionState.RUNNING, ConnectionState.READY, ConnectionState.CONNECTED].includes(deviceState.value)) {
		return FbUiVariantTypes.SUCCESS;
	}

	return FbUiVariantTypes.DEFAULT;
});
</script>
