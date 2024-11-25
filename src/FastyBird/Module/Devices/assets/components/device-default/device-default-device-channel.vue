<template>
	<fb-list-item :variant="ListItemVariantTypes.LIST">
		<template #icon>
			<channels-channel-icon
				:device="props.deviceData.device"
				:channel="props.channelData.channel"
			/>
		</template>

		<template #title>
			<el-text :line-clamp="2">
				{{ props.channelData.channel.title }}
			</el-text>
		</template>

		<template
			v-if="props.channelData.channel.hasComment"
			#subtitle
		>
			<el-text :line-clamp="2">
				{{ props.channelData.channel.comment }}
			</el-text>
		</template>

		<template #detail>
			<el-button-group>
				<el-button
					:icon="FasCircleInfo"
					size="small"
					plain
					@click="emit('detail', $event)"
				/>

				<el-button
					:icon="FasPencil"
					size="small"
					plain
					@click="emit('edit', $event)"
				/>

				<el-button
					:icon="FasTrash"
					type="warning"
					size="small"
					plain
					@click="emit('remove', $event)"
				/>
			</el-button-group>
		</template>
	</fb-list-item>
</template>

<script setup lang="ts">
import { ElButton, ElButtonGroup, ElText } from 'element-plus';

import { FasCircleInfo, FasPencil, FasTrash } from '@fastybird/web-ui-icons';
import { FbListItem, ListItemVariantTypes } from '@fastybird/web-ui-library';

import { IDeviceChannelEmits, IDeviceChannelProps } from '../../types';
import ChannelsChannelIcon from '../channels/channels-channel-icon.vue';

defineOptions({
	name: 'DeviceDefaultDeviceChannel',
});

const props = defineProps<IDeviceChannelProps>();

const emit = defineEmits<IDeviceChannelEmits>();
</script>
