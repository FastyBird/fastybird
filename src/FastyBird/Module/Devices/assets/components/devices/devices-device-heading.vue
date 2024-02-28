<template>
	<fb-layout-preview-heading>
		<template #heading>
			{{ useEntityTitle(props.device).value }}
		</template>

		<template
			v-if="props.device.hasComment"
			#subheading
		>
			{{ props.device.comment }}
		</template>

		<template #icon>
			<devices-device-icon :device="props.device" />
		</template>

		<template #buttons>
			<fb-ui-button
				v-if="props.editMode"
				:variant="FbUiButtonVariantTypes.OUTLINE_DANGER"
				:size="FbSizeTypes.EXTRA_SMALL"
				class="fb-devices-module-devices-device-heading__button"
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
				class="fb-devices-module-devices-device-heading__button"
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

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FbLayoutPreviewHeading, FbUiButton, FbSizeTypes, FbUiButtonVariantTypes } from '@fastybird/web-ui-library';

import { useEntityTitle } from '../../composables';
import { DevicesDeviceIcon } from '../../components';
import { IDevicesPreviewHeadingProps } from './devices-device-heading.types';

const props = withDefaults(defineProps<IDevicesPreviewHeadingProps>(), {
	editMode: false,
});

const emit = defineEmits<{
	(e: 'remove'): void;
	(e: 'configure'): void;
}>();

const { t } = useI18n();
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'devices-device-heading';
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
