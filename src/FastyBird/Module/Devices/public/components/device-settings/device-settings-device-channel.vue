<template>
	<fb-ui-item :variant="FbUiItemVariantTypes.LIST">
		<template #heading>
			{{ useEntityTitle(props.channelData.channel).value }}
		</template>

		<template
			v-if="props.channelData.channel.hasComment"
			#subheading
		>
			{{ props.channelData.channel.comment }}
		</template>

		<template #detail>
			<div class="fb-devices-module-device-settings-device-channel__buttons">
				<fb-ui-button
					:variant="FbUiButtonVariantTypes.OUTLINE_DEFAULT"
					:size="FbSizeTypes.EXTRA_SMALL"
					@click="emit('edit', props.channelData.channel.id)"
				>
					<font-awesome-icon icon="pencil-alt" />
					{{ t('buttons.edit.title') }}
				</fb-ui-button>

				<fb-ui-button
					v-if="resetControl !== null"
					:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
					:size="FbSizeTypes.EXTRA_SMALL"
					:disabled="!isDeviceReady"
					@click="onOpenView(DeviceSettingsDeviceChannelViewTypes.RESET)"
				>
					<font-awesome-icon icon="sync-alt" />
					{{ t('buttons.reset.title') }}
				</fb-ui-button>

				<fb-ui-button
					:variant="FbUiButtonVariantTypes.OUTLINE_DANGER"
					:size="FbSizeTypes.EXTRA_SMALL"
					@click="onOpenView(DeviceSettingsDeviceChannelViewTypes.REMOVE)"
				>
					<font-awesome-icon icon="trash" />
					{{ t('buttons.remove.title') }}
				</fb-ui-button>
			</div>
		</template>
	</fb-ui-item>

	<channel-settings-channel-reset
		v-if="activeView === DeviceSettingsDeviceChannelViewTypes.RESET && resetControl !== null"
		:device="props.device"
		:channel="props.channelData.channel"
		:control="resetControl"
		:transparent-bg="true"
		@reseted="onCloseView"
		@close="onCloseView"
	/>

	<channel-settings-channel-remove
		v-if="activeView === DeviceSettingsDeviceChannelViewTypes.REMOVE"
		:device="props.device"
		:channel="props.channelData.channel"
		:transparent-bg="true"
		@removed="onCloseView"
		@close="onCloseView"
	/>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FbUiButton, FbUiItem, FbSizeTypes, FbUiItemVariantTypes, FbUiButtonVariantTypes } from '@fastybird/web-ui-library';
import { ControlName } from '@fastybird/metadata-library';

import { useDeviceState, useEntityTitle } from '@/composables';
import { IChannelControl } from '@/models/types';
import { ChannelSettingsChannelRemove, ChannelSettingsChannelReset } from '@/components';
import {
	IDeviceSettingsDevicePropertyProps,
	DeviceSettingsDeviceChannelViewTypes,
} from '@/components/device-settings/device-settings-device-channel.types';

const props = defineProps<IDeviceSettingsDevicePropertyProps>();

const emit = defineEmits<{
	(e: 'edit', id: string): void;
}>();

const { t } = useI18n();

const activeView = ref<DeviceSettingsDeviceChannelViewTypes>(DeviceSettingsDeviceChannelViewTypes.NONE);

const { isReady: isDeviceReady } = useDeviceState(props.device);

const resetControl = computed<IChannelControl | null>((): IChannelControl | null => {
	const control = props.channelData.controls.find((control) => control.name === ControlName.RESET);

	return control ?? null;
});

const onOpenView = (view: DeviceSettingsDeviceChannelViewTypes): void => {
	activeView.value = view;
};

const onCloseView = (): void => {
	activeView.value = DeviceSettingsDeviceChannelViewTypes.NONE;
};
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'device-settings-device-channel';
</style>

<i18n>
{
  "en": {
    "buttons": {
      "edit": {
        "title": "Edit"
      },
      "reset": {
        "title": "Reset"
      },
      "remove": {
        "title": "Remove"
      }
    }
  }
}
</i18n>
