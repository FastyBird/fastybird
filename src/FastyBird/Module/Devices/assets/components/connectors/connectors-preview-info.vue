<template>
	<fb-ui-content
		:ml="FbSizeTypes.MEDIUM"
		class="fb-devices-module-connectors-preview-info__container"
	>
		<fb-ui-media-item>
			<template #left>
				<font-awesome-icon
					icon="plug"
					size="2x"
				/>
			</template>

			<template #heading>
				{{ t('headings.allConnectors') }}
			</template>

			<template #description>
				{{ t('subHeadings.allConnectors', { count: props.total }, props.total) }}
			</template>
		</fb-ui-media-item>

		<fb-ui-media-item>
			<template #left>
				<font-awesome-icon
					icon="plus"
					size="2x"
				/>
			</template>

			<template #heading>
				{{ t('headings.registerConnector') }}
			</template>

			<template #description>
				{{ t('subHeadings.registerConnector') }}
			</template>

			<template #action>
				<fb-ui-button
					:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
					:size="FbSizeTypes.MEDIUM"
					@click="emit('register')"
				>
					{{ t('buttons.add.title') }}
				</fb-ui-button>
			</template>
		</fb-ui-media-item>

		<fb-ui-media-item>
			<template #left>
				<font-awesome-icon
					icon="sync-alt"
					size="2x"
				/>
			</template>

			<template #heading>
				{{ t('headings.syncConnectors') }}
			</template>

			<template #description>
				{{ t('subHeadings.syncConnectors') }}
			</template>

			<template #action>
				<fb-ui-button
					:variant="FbUiButtonVariantTypes.OUTLINE_DEFAULT"
					:size="FbSizeTypes.MEDIUM"
					@click="emit('synchronise')"
				>
					{{ t('buttons.sync.title') }}
				</fb-ui-button>
			</template>
		</fb-ui-media-item>
	</fb-ui-content>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FbUiButton, FbUiMediaItem, FbUiContent, FbSizeTypes, FbUiButtonVariantTypes } from '@fastybird/web-ui-library';

import { IConnectorsDesktopInfoProps } from './connectors-preview-info.types';

const props = defineProps<IConnectorsDesktopInfoProps>();

const emit = defineEmits<{
	(e: 'register'): void;
	(e: 'synchronise'): void;
}>();

const { t } = useI18n();
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'connectors-preview-info';
</style>

<i18n>
{
  "en": {
    "headings": {
      "allConnectors": "All connectors",
      "registerConnector": "New connector",
      "syncConnectors": "Sync connectors"
    },
    "subHeadings": {
      "allConnectors": "No connectors registered | One connector registered | {count} connectors registered",
      "registerConnector": "Register new connector to the system",
      "syncConnectors": "Sync all connectors with server"
    },
    "buttons": {
      "add": {
        "title": "Register new connector"
      },
      "sync": {
        "title": "Synchronise"
      }
    }
  }
}
</i18n>
