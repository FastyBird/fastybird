<template>
	<fb-list-item :variant="ListItemVariantTypes.LIST">
		<template #title>
			{{ useEntityTitle(props.channelData.channel).value }}
		</template>

		<template
			v-if="props.channelData.channel.hasComment"
			#subtitle
		>
			{{ props.channelData.channel.comment }}
		</template>

		<template #detail>
			<el-button
				:icon="FasPencil"
				size="small"
				plain
				@click="emit('edit', $event)"
			/>

			<el-button
				v-if="resetControl !== null"
				:icon="FasRotate"
				:disabled="!isDeviceReady"
				type="warning"
				size="small"
				plain
				@click="emit('reset', $event)"
			/>

			<el-button
				:icon="FasTrash"
				type="warning"
				size="small"
				plain
				@click="emit('remove', $event)"
			/>
		</template>
	</fb-list-item>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { ElButton } from 'element-plus';

import { FasPencil, FasRotate, FasTrash } from '@fastybird/web-ui-icons';
import { FbListItem, ListItemVariantTypes } from '@fastybird/web-ui-library';
import { ControlName } from '@fastybird/metadata-library';

import { useDeviceState, useEntityTitle } from '../../composables';
import { IChannelControl } from '../../models/types';
import { IDeviceSettingsDevicePropertyProps } from './device-settings-device-channel.types';

defineOptions({
	name: 'DeviceSettingsDeviceChannel',
});

const props = defineProps<IDeviceSettingsDevicePropertyProps>();

const emit = defineEmits<{
	(e: 'edit', event: Event): void;
	(e: 'remove', event: Event): void;
	(e: 'reset', event: Event): void;
}>();

const { isReady: isDeviceReady } = useDeviceState(props.device);

const resetControl = computed<IChannelControl | null>((): IChannelControl | null => {
	const control = props.channelData.controls.find((control) => control.name === ControlName.RESET);

	return control ?? null;
});
</script>
