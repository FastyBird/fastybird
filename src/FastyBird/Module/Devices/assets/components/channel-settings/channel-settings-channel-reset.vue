<template>
	<fb-ui-confirmation-window
		:transparent-bg="props.transparentBg"
		@confirm="onReset"
		@close="onClose"
	>
		<template #icon>
			<font-awesome-icon
				icon="sync-alt"
				size="6x"
			/>
		</template>

		<template #title>
			{{ t('headings.clear') }}
		</template>

		<i18n-t
			keypath="messages.confirmClearing"
			tag="p"
		>
			<template #device>
				<strong>{{ useEntityTitle(props.device).value }}</strong>
			</template>

			<template #channel>
				<strong>{{ useEntityTitle(props.channel).value }}</strong>
			</template>
		</i18n-t>
	</fb-ui-confirmation-window>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';

import { FbUiConfirmationWindow } from '@fastybird/web-ui-library';

import { useDeviceState, useEntityTitle, useFlashMessage } from '../../composables';
import { useChannelControls } from '../../models';
import { IChannelSettingsChannelResetProps } from './channel-settings-channel-reset.types';

const props = withDefaults(defineProps<IChannelSettingsChannelResetProps>(), {
	transparentBg: false,
});

const emit = defineEmits<{
	(e: 'close'): void;
	(e: 'cleared'): void;
}>();

const { t } = useI18n();
const flashMessage = useFlashMessage();

const channelControlsStore = useChannelControls();
const { isReady: isDeviceReady } = useDeviceState(props.device);

const onReset = async (): Promise<void> => {
	if (!isDeviceReady.value) {
		flashMessage.error(
			t('messages.notOnline', {
				device: useEntityTitle(props.device).value,
			})
		);

		return;
	}

	try {
		await channelControlsStore.transmitCommand({ id: props.control.id });
	} catch (e) {
		flashMessage.error(
			t('messages.notCleared', {
				device: useEntityTitle(props.device).value,
			})
		);
	}

	emit('cleared');
};

const onClose = (): void => {
	emit('close');
};
</script>

<i18n>
{
  "en": {
    "headings": {
      "clear": "Clear channel"
    },
    "messages": {
      "confirmClearing": "Are you sure to clear measured values for device {device} and channel {channel} ?",
      "notCleared": "Channel {channel} couldn't be cleared."
    }
  }
}
</i18n>
