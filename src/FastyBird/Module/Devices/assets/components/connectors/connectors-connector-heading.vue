<template>
	<div class="flex flex-row items-center px-4 py-1 b-b b-b-solid h-[2rem]">
		<connectors-connector-icon
			:connector="props.connector"
			:size="30"
			class="mr-2"
		/>

		<h2 class="font-size-[1rem] font-500 m-0 p-0 flex-grow">
			{{ useEntityTitle(props.connector).value }}

			<small
				v-if="props.connector.hasComment"
				class="font-400 font-size-[75%] block"
			>
				{{ props.connector.comment }}
			</small>
		</h2>

		<el-button
			v-if="props.editMode"
			type="warning"
			size="small"
			plain
			@click="emit('remove', $event)"
		>
			<template #icon>
				<fas-trash />
			</template>
			{{ t('buttons.remove.title') }}
		</el-button>

		<el-button
			v-if="props.editMode"
			type="primary"
			size="small"
			@click="emit('configure', $event)"
		>
			<template #icon>
				<fas-gears />
			</template>
			{{ t('buttons.configure.title') }}
		</el-button>
	</div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { ElButton } from 'element-plus';

import { FasGears, FasTrash } from '@fastybird/web-ui-icons';

import { useEntityTitle } from '../../composables';
import { ConnectorsConnectorIcon } from '../../components';
import { IConnectorsPreviewHeadingProps } from './connectors-connector-heading.types';

defineOptions({
	name: 'ConnectorsConnectorHeading',
});

const props = withDefaults(defineProps<IConnectorsPreviewHeadingProps>(), {
	editMode: false,
});

const emit = defineEmits<{
	(e: 'remove', event: Event): void;
	(e: 'configure', event: Event): void;
}>();

const { t } = useI18n();
</script>
