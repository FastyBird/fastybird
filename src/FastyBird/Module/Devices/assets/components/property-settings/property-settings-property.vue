<template>
	<fb-list-item :variant="ListItemVariantTypes.LIST">
		<template #title>
			{{ useEntityTitle(props.property).value }}
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
import { IPropertySettingsPropertyProps } from './property-settings-property.types';

defineOptions({
	name: 'PropertySettingsProperty',
});

const props = defineProps<IPropertySettingsPropertyProps>();

const emit = defineEmits<{
	(e: 'edit', event: Event): void;
	(e: 'remove', event: Event): void;
}>();
</script>
