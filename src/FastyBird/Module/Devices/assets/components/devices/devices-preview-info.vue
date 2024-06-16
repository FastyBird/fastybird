<template>
	<div class="flex flex-col justify-center">
		<fb-media-item class="my-5">
			<template #left>
				<el-icon :size="32">
					<fas-plug />
				</el-icon>
			</template>

			<template #heading>
				{{ t('headings.devices.allDevices') }}
			</template>

			<template #description>
				{{ t('subHeadings.devices.allDevices', { count: props.total }, props.total) }}
			</template>
		</fb-media-item>

		<fb-media-item class="my-5">
			<template #left>
				<el-icon :size="32">
					<fas-plus />
				</el-icon>
			</template>

			<template #heading>
				{{ t('headings.devices.new') }}
			</template>

			<template #description>
				{{ t('subHeadings.devices.new') }}
			</template>

			<template #action>
				<el-button
					type="primary"
					plain
					@click="emit('register', $event)"
				>
					{{ t('buttons.addDevice.title') }}
				</el-button>
			</template>
		</fb-media-item>

		<fb-media-item class="my-5">
			<template #left>
				<el-icon :size="32">
					<fas-arrows-rotate />
				</el-icon>
			</template>

			<template #heading>
				{{ t('headings.devices.sync') }}
			</template>

			<template #description>
				{{ t('subHeadings.devices.sync') }}
			</template>

			<template #action>
				<el-button
					type="info"
					plain
					@click="emit('synchronise', $event)"
				>
					{{ t('buttons.sync.title') }}
				</el-button>
			</template>
		</fb-media-item>
	</div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { ElButton, ElIcon } from 'element-plus';

import { FasPlus, FasPlug, FasArrowsRotate } from '@fastybird/web-ui-icons';
import { FbMediaItem } from '@fastybird/web-ui-library';

import { IDevicesDesktopInfoProps } from './devices-preview-info.types';

defineOptions({
	name: 'DevicesPreviewInfo',
});

const props = defineProps<IDevicesDesktopInfoProps>();

const emit = defineEmits<{
	(e: 'register', event: Event): void;
	(e: 'synchronise', event: Event): void;
}>();

const { t } = useI18n();
</script>
