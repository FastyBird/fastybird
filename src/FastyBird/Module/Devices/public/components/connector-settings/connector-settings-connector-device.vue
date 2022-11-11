<template>
	<fb-ui-item :variant="FbUiItemVariantTypes.LIST">
		<template #heading>
			{{ useEntityTitle(props.deviceData.device).value }}
		</template>

		<template
			v-if="props.deviceData.device.hasComment"
			#subheading
		>
			{{ props.deviceData.device.comment }}
		</template>

		<template #detail>
			<div class="fb-devices-module-connector-settings-connector-device__buttons">
				<fb-ui-button
					:variant="FbUiButtonVariantTypes.OUTLINE_DEFAULT"
					:size="FbSizeTypes.EXTRA_SMALL"
					@click="emit('edit', props.deviceData.device.id)"
				>
					<font-awesome-icon icon="pencil-alt" />
					{{ t('buttons.edit.title') }}
				</fb-ui-button>

				<fb-ui-button
					:variant="FbUiButtonVariantTypes.OUTLINE_DANGER"
					:size="FbSizeTypes.EXTRA_SMALL"
					@click="onOpenView(ViewTypes.REMOVE)"
				>
					<font-awesome-icon icon="trash" />
					{{ t('buttons.remove.title') }}
				</fb-ui-button>
			</div>
		</template>
	</fb-ui-item>

	<device-settings-device-remove
		v-if="activeView === ViewTypes.REMOVE"
		:device="props.deviceData.device"
		:transparent-bg="true"
		@removed="onCloseView"
		@close="onCloseView"
	/>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FbUiButton, FbUiItem, FbSizeTypes, FbUiItemVariantTypes, FbUiButtonVariantTypes } from '@fastybird/web-ui-library';

import { useEntityTitle } from '@/composables';
import { IConnector } from '@/models/types';
import { DeviceSettingsDeviceRemove } from '@/components';
import { IDeviceData } from '@/types/devices-module';

enum ViewTypes {
	NONE = 'none',
	REMOVE = 'remove',
}

interface IConnectorSettingsConnectorPropertyProps {
	connector: IConnector;
	deviceData: IDeviceData;
}

const props = defineProps<IConnectorSettingsConnectorPropertyProps>();

const emit = defineEmits<{
	(e: 'edit', id: string): void;
}>();

const { t } = useI18n();

const activeView = ref<ViewTypes>(ViewTypes.NONE);

const onOpenView = (view: ViewTypes): void => {
	activeView.value = view;
};

const onCloseView = (): void => {
	activeView.value = ViewTypes.NONE;
};
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'connector-settings-connector-device';
</style>

<i18n>
{
  "en": {
    "buttons": {
      "edit": {
        "title": "Edit"
      },
      "remove": {
        "title": "Remove"
      }
    }
  }
}
</i18n>
