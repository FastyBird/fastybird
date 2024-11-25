<template>
	<fb-list-item :variant="ListItemVariantTypes.LIST">
		<template #icon>
			<devices-device-icon :device="props.deviceData.device" />
		</template>

		<template #title>
			<el-text :line-clamp="2">
				{{ props.deviceData.device.title }}
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

import { IConnectorDeviceEmits, IConnectorDeviceProps } from '../../types';
import DevicesDeviceIcon from '../devices/devices-device-icon.vue';

defineOptions({
	name: 'ConnectorDefaultConnectorDevice',
});

const props = defineProps<IConnectorDeviceProps>();

const emit = defineEmits<IConnectorDeviceEmits>();
</script>
