<template>
	<fb-layout-preview-heading>
		<template #heading>
			{{ useEntityTitle(props.connector).value }}
		</template>

		<template
			v-if="props.connector.hasComment"
			#subheading
		>
			{{ props.connector.comment }}
		</template>

		<template #icon>
			<connectors-connector-icon :connector="props.connector" />
		</template>

		<template #buttons>
			<fb-ui-button
				v-if="props.editMode"
				:variant="FbUiButtonVariantTypes.OUTLINE_DANGER"
				:size="FbSizeTypes.EXTRA_SMALL"
				class="fb-devices-module-connectors-connector-heading__button"
				@click="emit('remove')"
			>
				<template #icon>
					<font-awesome-icon icon="trash-alt" />
				</template>
				{{ t('buttons.remove.title') }}
			</fb-ui-button>

			<fb-ui-button
				v-if="props.editMode"
				:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
				:size="FbSizeTypes.EXTRA_SMALL"
				class="fb-devices-module-connectors-connector-heading__button"
				@click="emit('configure')"
			>
				<template #icon>
					<font-awesome-icon icon="cogs" />
				</template>
				{{ t('buttons.configure.title') }}
			</fb-ui-button>
		</template>
	</fb-layout-preview-heading>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';

import { FbLayoutPreviewHeading, FbUiButton, FbSizeTypes, FbUiButtonVariantTypes } from '@fastybird/web-ui-library';

import { useEntityTitle } from '../../composables';
import { ConnectorsConnectorIcon } from '../../components';
import { IConnectorsPreviewHeadingProps } from './connectors-connector-heading.types';

const props = withDefaults(defineProps<IConnectorsPreviewHeadingProps>(), {
	editMode: false,
});

const emit = defineEmits<{
	(e: 'remove'): void;
	(e: 'configure'): void;
}>();

const { t } = useI18n();
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'connectors-connector-heading';
</style>

<i18n>
{
  "en": {
    "buttons": {
      "remove": {
        "title": "Remove"
      },
      "configure": {
        "title": "Configure"
      }
    }
  }
}
</i18n>
