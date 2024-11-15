<template>
	<fb-list-item :variant="ListItemVariantTypes.LIST">
		<template #title>
			{{ props.title ?? props.property.title }}
		</template>

		<template
			v-if="props.title"
			#subtitle
		>
			{{ props.property.identifier }}
		</template>

		<template #detail>
			<el-button-group>
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
import { ElButton, ElButtonGroup } from 'element-plus';

import { FasPencil, FasTrash } from '@fastybird/web-ui-icons';
import { FbListItem, ListItemVariantTypes } from '@fastybird/web-ui-library';

import { IPropertyDefaultPropertySettingsProps } from './property-default-property-settings.types';

defineOptions({
	name: 'PropertyDefaultPropertySettings',
});

const props = defineProps<IPropertyDefaultPropertySettingsProps>();

const emit = defineEmits<{
	(e: 'edit', event: Event): void;
	(e: 'remove', event: Event): void;
}>();
</script>
