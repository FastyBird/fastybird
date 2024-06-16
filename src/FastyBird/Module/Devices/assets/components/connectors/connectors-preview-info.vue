<template>
	<div class="flex flex-col justify-center">
		<fb-media-item class="my-5">
			<template #left>
				<el-icon :size="32">
					<fas-ethernet />
				</el-icon>
			</template>

			<template #heading>
				{{ t('headings.connectors.allConnectors') }}
			</template>

			<template #description>
				{{ t('subHeadings.connectors.allConnectors', { count: props.total }, props.total) }}
			</template>
		</fb-media-item>

		<fb-media-item class="my-5">
			<template #left>
				<el-icon :size="32">
					<fas-plus />
				</el-icon>
			</template>

			<template #heading>
				{{ t('headings.connectors.new') }}
			</template>

			<template #description>
				{{ t('subHeadings.connectors.new') }}
			</template>

			<template #action>
				<el-button
					type="primary"
					plain
					@click="emit('register', $event)"
				>
					{{ t('buttons.addConnector.title') }}
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
				{{ t('headings.connectors.sync') }}
			</template>

			<template #description>
				{{ t('subHeadings.connectors.sync') }}
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

import { FasPlus, FasEthernet, FasArrowsRotate } from '@fastybird/web-ui-icons';
import { FbMediaItem } from '@fastybird/web-ui-library';

import { IConnectorsDesktopInfoProps } from './connectors-preview-info.types';

defineOptions({
	name: 'ConnectorsPreviewInfo',
});

const props = defineProps<IConnectorsDesktopInfoProps>();

const emit = defineEmits<{
	(e: 'register', event: Event): void;
	(e: 'synchronise', event: Event): void;
}>();

const { t } = useI18n();
</script>
