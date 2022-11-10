<template>
	<template v-if="channel !== null">
		<template v-if="isExtraSmallDevice || isMounted">
			<fb-layout-header-heading
				:heading="channel.draft ? t('headings.add') : t('headings.edit')"
				:sub-heading="useEntityTitle(device).value"
			/>

			<template v-if="isExtraSmallDevice">
				<fb-layout-header-button
					:action-type="FbMenuItemTypes.BUTTON"
					small
					left
					@click="onClose"
				>
					{{ t('buttons.close.title') }}
				</fb-layout-header-button>

				<fb-layout-header-button
					:action-type="FbMenuItemTypes.BUTTON"
					small
					right
					@click="onSubmit"
				>
					{{ t('buttons.save.title') }}
				</fb-layout-header-button>
			</template>
		</template>

		<channel-settings-channel-settings
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			:device="device"
			:channel-data="channelData"
			@created="onCreated"
		/>

		<fb-ui-content
			v-if="!isExtraSmallDevice"
			:pv="FbSizeTypes.MEDIUM"
			:ph="FbSizeTypes.MEDIUM"
			class="fb-devices-module-view-channel-settings__buttons"
		>
			<fb-ui-button
				:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
				:size="FbSizeTypes.MEDIUM"
				:loading="remoteFormResult === FbFormResultTypes.WORKING"
				:disabled="remoteFormResult !== FbFormResultTypes.NONE"
				uppercase
				class="fb-devices-module-view-channel-settings__buttons-save"
				@click="onSubmit"
			>
				{{ t('buttons.save.title') }}
			</fb-ui-button>

			<fb-ui-button
				:variant="FbUiButtonVariantTypes.LINK_DEFAULT"
				:size="FbSizeTypes.MEDIUM"
				:disabled="remoteFormResult !== FbFormResultTypes.NONE"
				uppercase
				class="fb-devices-module-view-channel-settings__buttons-close"
				@click="onClose"
			>
				{{ t('buttons.close.title') }}
			</fb-ui-button>
		</fb-ui-content>
	</template>
</template>

<script setup lang="ts">
import { computed, ComputedRef, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRoute, useRouter } from 'vue-router';

import {
	FbLayoutHeaderButton,
	FbLayoutHeaderHeading,
	FbUiButton,
	FbUiContent,
	FbMenuItemTypes,
	FbSizeTypes,
	FbUiButtonVariantTypes,
	FbFormResultTypes,
} from '@fastybird/web-ui-library';
import { ModuleSource } from '@fastybird/metadata-library';

import { useBreakpoints, useEntityTitle, useRoutesNames, useUuid } from '@/lib/composables';
import { useChannelControls, useChannelProperties, useChannels, useDevices } from '@/lib/models';
import { IChannel, IChannelControl, IChannelProperty, IDevice } from '@/lib/models/types';
import { ChannelSettingsChannelSettings } from '@/lib/components';
import { IChannelData } from '@/types/devices-module';
import { orderBy } from 'natural-orderby';

interface IViewDeviceChanelSettingsProps {
	id: string;
	channelId?: string | null;
	connectorId: string;
}

const props = withDefaults(defineProps<IViewDeviceChanelSettingsProps>(), {
	channelId: null,
});

const { t } = useI18n();
const router = useRouter();
const route = useRoute();

const { isExtraSmallDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const { generate: generateUuid, validate: validateUuid } = useUuid();

const devicesStore = useDevices();
const channelsStore = useChannels();
const channelControlsStore = useChannelControls();
const channelPropertiesStore = useChannelProperties();

if (props.channelId !== null && !validateUuid(props.channelId)) {
	throw new Error('Channel identifier is not valid');
}

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FbFormResultTypes>(FbFormResultTypes.NONE);

const isConnectorSettingsRoute = computed<boolean>((): boolean => {
	return (
		route.matched.find((matched) => {
			return matched.name === routeNames.connectorSettingsEditDeviceAddChannel || matched.name === routeNames.connectorSettingsEditDeviceEditChannel;
		}) !== undefined
	);
});

const isMounted = ref<boolean>(false);

const device = computed<IDevice>((): IDevice => {
	if (!validateUuid(props.id)) {
		throw new Error('Device identifier is not valid');
	}

	const device = devicesStore.findById(props.id);

	if (device === null) {
		throw new Error('Device was not found');
	}

	return device;
});

let channel: ComputedRef<IChannel | null>;

if (props.channelId === null) {
	const { id } = await channelsStore.add({
		device: device.value,
		type: { source: ModuleSource.MODULE_DEVICES },
		draft: true,
		data: {
			identifier: generateUuid().toString(),
		},
	});

	channel = computed<IChannel | null>((): IChannel | null => channelsStore.findById(id));
} else {
	channel = computed<IChannel | null>((): IChannel | null => channelsStore.findById(props.channelId as string));
}

const channelData = computed<IChannelData>((): IChannelData => {
	if (channel.value === null) {
		throw new Error('Channel was not found');
	}

	return {
		channel: channel.value,
		controls: orderBy<IChannelControl>(
			channelControlsStore.findForChannel(channel.value.id).filter((control) => (channel.value?.draft ? true : !control.draft)),
			[(v): string => v.name],
			['asc']
		),
		properties: orderBy<IChannelProperty>(
			channelPropertiesStore.findForChannel(channel.value.id).filter((property) => (channel.value?.draft ? true : !property.draft)),
			[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
			['asc']
		),
	};
});

const onSubmit = (): void => {
	remoteFormSubmit.value = true;
};

const onClose = (): void => {
	if (isConnectorSettingsRoute.value) {
		router.push({ name: routeNames.connectorSettingsEditDevice, params: { id: props.connectorId, deviceId: props.id } });
	} else {
		router.push({ name: routeNames.deviceSettings, params: { id: props.id } });
	}
};

const onCreated = (channel: IChannel): void => {
	if (isConnectorSettingsRoute.value) {
		router.push({
			name: routeNames.connectorSettingsEditDeviceEditChannel,
			params: { id: props.connectorId, deviceId: props.id, channelId: channel.id },
		});
	} else {
		router.push({ name: routeNames.deviceSettingsEditChannel, params: { id: props.id, channelId: channel.id } });
	}
};

onMounted((): void => {
	isMounted.value = true;
});

onUnmounted((): void => {
	if (channel.value?.draft) {
		channelsStore.remove({ id: channel.value.id });
	}
});

useMeta(() => ({
	title: t('meta.title', { device: useEntityTitle(channelData.value.channel).value }),
}));
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'view-channel-settings';
</style>

<i18n>
{
  "en": {
    "meta": {
      "title": "Device channel settings: {channel}"
    },
    "headings": {
      "add": "Ad device's new channel",
      "edit": "Configure device's channel"
    },
    "buttons": {
      "save": {
        "title": "Save"
      },
      "close": {
        "title": "Close"
      }
    }
  }
}
</i18n>
