<template>
	{{ state }}
</template>

<script setup lang="ts">
import { useDeviceState } from '../../composables';

import { IDevicesListDevicesColumnStateProps } from './devices-list-devices-column-state.types';

defineOptions({
	name: 'DevicesListDevicesColumnState',
});

const props = defineProps<IDevicesListDevicesColumnStateProps>();

const { state } = useDeviceState(props.device);
</script>
