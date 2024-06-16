<template>
	<div class="flex flex-row justify-between p-2 b-b b-b-solid">
		<div>
			<el-button
				size="small"
				link
				@click="emit('close', $event)"
			>
				<template #icon>
					<fas-xmark />
				</template>

				{{ t('buttons.close.title') }}
			</el-button>

			<el-button
				v-if="!props.editMode"
				size="small"
				link
				@click="emit('toggleEdit', $event)"
			>
				<template #icon>
					<fas-pencil />
				</template>

				{{ t('buttons.edit.title') }}
			</el-button>

			<el-button
				v-if="props.editMode"
				size="small"
				link
				@click="emit('toggleEdit', $event)"
			>
				<template #icon>
					<fas-check />
				</template>

				{{ t('buttons.done.title') }}
			</el-button>
		</div>

		<div>
			<i18n-t
				:i18n="i18n"
				keypath="misc.paging"
				tag="div"
				class="inline mr-2"
			>
				<template #page>
					{{ props.page }}
				</template>

				<template #total>
					{{ props.total }}
				</template>
			</i18n-t>

			<el-button
				:disabled="props.page <= 1"
				size="small"
				link
				@click="emit('previous', $event)"
			>
				<template #icon>
					<fas-angle-left />
				</template>
			</el-button>

			<el-button
				:disabled="props.page >= props.total"
				size="small"
				link
				@click="emit('next', $event)"
			>
				<template #icon>
					<fas-angle-right />
				</template>
			</el-button>
		</div>
	</div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { ElButton } from 'element-plus';

import { FasXmark, FasPencil, FasCheck, FasAngleLeft, FasAngleRight } from '@fastybird/web-ui-icons';

import { IDevicesPreviewToolbarProps } from './devices-device-toolbar.types';

defineOptions({
	name: 'DevicesDeviceToolbar',
});

const props = withDefaults(defineProps<IDevicesPreviewToolbarProps>(), {
	editMode: false,
});

const emit = defineEmits<{
	(e: 'toggleEdit', event: Event): void;
	(e: 'previous', event: Event): void;
	(e: 'next', event: Event): void;
	(e: 'close', event: Event): void;
}>();

const i18n = useI18n();
const { t } = i18n;
</script>
