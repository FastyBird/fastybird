<template>
	<fb-list-item :variant="ListItemVariantTypes.LIST">
		<template #title>
			{{ useEntityTitle(props.deviceData.device).value }}
		</template>

		<template
			v-if="props.deviceData.device.hasComment"
			#subtitle
		>
			{{ props.deviceData.device.comment }}
		</template>

		<template #detail>
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
		</template>
	</fb-list-item>
</template>

<script setup lang="ts">
import { ElButton } from 'element-plus';

import { FasPencil, FasTrash } from '@fastybird/web-ui-icons';
import { FbListItem, ListItemVariantTypes } from '@fastybird/web-ui-library';

import { useEntityTitle } from '../../composables';
import { IConnectorSettingsConnectorPropertyProps } from './connector-settings-connector-device.types';

defineOptions({
	name: 'ConnectorSettingsConnectorDevice',
});

const props = defineProps<IConnectorSettingsConnectorPropertyProps>();

const emit = defineEmits<{
	(e: 'edit', event: Event): void;
	(e: 'remove', event: Event): void;
}>();
</script>
