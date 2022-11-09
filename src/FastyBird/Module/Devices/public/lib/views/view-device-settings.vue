<template>
	<template v-if="isExtraSmallDevice || isMounted">
		<fb-layout-header-heading
			:heading="isConnectorSettingsRoute ? (deviceData.device.draft ? t('headings.add') : t('headings.edit')) : t('headings.configure')"
			:sub-heading="isConnectorSettingsRoute ? useEntityTitle(connector).value : useEntityTitle(deviceData.device).value"
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

	<device-settings-device-settings
		v-model:remote-form-submit="remoteFormSubmit"
		v-model:remote-form-result="remoteFormResult"
		:connector="connector"
		:device-data="deviceData"
		@add-channel="onAddChannel"
		@edit-channel="onEditChannel"
		@created="onDeviceCreated"
	/>

	<fb-ui-content
		v-if="!isExtraSmallDevice"
		:pv="FbSizeTypes.MEDIUM"
		:ph="FbSizeTypes.MEDIUM"
		class="fb-devices-module-view-device-settings__buttons"
	>
		<fb-ui-button
			:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
			:size="FbSizeTypes.MEDIUM"
			:loading="remoteFormResult === FbFormResultTypes.WORKING"
			:disabled="remoteFormResult !== FbFormResultTypes.NONE"
			uppercase
			class="fb-devices-module-view-device-settings__buttons-save"
			@click="onSubmit"
		>
			{{ t('buttons.save.title') }}
		</fb-ui-button>

		<fb-ui-button
			:variant="FbUiButtonVariantTypes.LINK_DEFAULT"
			:size="FbSizeTypes.MEDIUM"
			:disabled="remoteFormResult !== FbFormResultTypes.NONE"
			uppercase
			class="fb-devices-module-view-device-settings__buttons-close"
			@click="onClose"
		>
			{{ t('buttons.close.title') }}
		</fb-ui-button>
	</fb-ui-content>
</template>

<script setup lang="ts">
import { computed, ComputedRef, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRoute, useRouter } from 'vue-router';
import { orderBy } from 'natural-orderby';

import {
	FbLayoutHeaderButton,
	FbLayoutHeaderHeading,
	FbUiButton,
	FbUiContent,
	FbSizeTypes,
	FbMenuItemTypes,
	FbUiButtonVariantTypes,
	FbFormResultTypes,
} from '@fastybird/web-ui-library';

import { useBreakpoints, useEntityTitle, useRoutesNames, useUuid } from '@/lib/composables';
import {
	useChannelControls,
	useChannelProperties,
	useChannels,
	useConnectors,
	useDeviceControls,
	useDeviceProperties,
	useDevices,
} from '@/lib/models';
import { IConnector, IChannelControl, IChannelProperty, IDevice, IDeviceControl, IDeviceProperty } from '@/lib/models/types';
import { DeviceSettingsDeviceSettings } from '@/lib/components';
import { IChannelData, IDeviceData } from '@/types/devices-module';

interface IViewDeviceSettingsProps {
	id: string | null;
	connectorId: string;
}

const props = defineProps<IViewDeviceSettingsProps>();

const { t } = useI18n();
const router = useRouter();
const route = useRoute();

const { isExtraSmallDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const { generate: generateUuid, validate: validateUuid } = useUuid();

const connectorsStore = useConnectors();
const devicesStore = useDevices();
const deviceControlsStore = useDeviceControls();
const devicePropertiesStore = useDeviceProperties();
const channelsStore = useChannels();
const channelControlsStore = useChannelControls();
const channelPropertiesStore = useChannelProperties();

if (props.id !== null && !validateUuid(props.id)) {
	throw new Error('Device identifier is not valid');
}

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FbFormResultTypes>(FbFormResultTypes.NONE);

const isConnectorSettingsRoute = computed<boolean>((): boolean => {
	return (
		route.matched.find((matched) => {
			return (
				matched.name === routeNames.connectorSettings ||
				matched.name === routeNames.connectorSettingsAddDevice ||
				matched.name === routeNames.connectorSettingsEditDevice
			);
		}) !== undefined
	);
});

const isMounted = ref<boolean>(false);

const connector = computed<IConnector>((): IConnector => {
	if (!validateUuid(props.connectorId)) {
		throw new Error('Connector identifier is not valid');
	}

	const connector = connectorsStore.findById(props.connectorId);

	if (connector === null) {
		throw new Error('Connector was not found');
	}

	return connector;
});

let device: ComputedRef<IDevice | null>;

if (props.id === null) {
	const { id } = await devicesStore.add({
		connector: connector.value,
		type: { source: connector.value.type.source, type: connector.value.type.type },
		draft: true,
		data: {
			identifier: generateUuid().toString(),
		},
	});

	device = computed<IDevice | null>((): IDevice | null => devicesStore.findById(id));
} else {
	device = computed<IDevice | null>((): IDevice | null => devicesStore.findById(props.id as string));
}

const deviceData = computed<IDeviceData>((): IDeviceData => {
	if (device.value === null) {
		throw new Error('Device was not found');
	}

	return {
		device: device.value,
		controls: orderBy<IDeviceControl>(
			deviceControlsStore.findForDevice(device.value.id).filter((control) => (device.value?.draft ? true : !control.draft)),
			[(v): string => v.name],
			['asc']
		),
		properties: orderBy<IDeviceProperty>(
			devicePropertiesStore.findForDevice(device.value.id).filter((property) => (device.value?.draft ? true : !property.draft)),
			[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
			['asc']
		),
		channels: orderBy<IChannelData>(
			channelsStore.findForDevice(device.value.id).map((channel): IChannelData => {
				return {
					channel,
					controls: orderBy<IChannelControl>(
						channelControlsStore.findForChannel(channel.id).filter((control) => (channel.draft ? true : !control.draft)),
						[(v): string => v.name],
						['asc']
					),
					properties: orderBy<IChannelProperty>(
						channelPropertiesStore.findForChannel(channel.id).filter((property) => (channel.draft ? true : !property.draft)),
						[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
						['asc']
					),
				};
			}),
			[(v): string => v.channel.name ?? v.channel.identifier, (v): string => v.channel.identifier],
			['asc']
		),
	};
});

const onSubmit = (): void => {
	remoteFormSubmit.value = true;
};

const onClose = (): void => {
	if (isConnectorSettingsRoute.value) {
		router.push({ name: routeNames.connectorSettings, params: { id: props.connectorId } });
	} else {
		router.push({ name: routeNames.deviceDetail, params: { id: props.id } });
	}
};

const onAddChannel = (): void => {
	if (isConnectorSettingsRoute.value) {
		router.push({ name: routeNames.connectorSettingsEditDeviceAddChannel, params: { id: connector.value?.id, deviceId: props.id } });
	} else {
		router.push({ name: routeNames.deviceSettingsAddChannel, params: { id: props.id } });
	}
};

const onEditChannel = (id: string): void => {
	if (isConnectorSettingsRoute.value) {
		router.push({ name: routeNames.connectorSettingsEditDeviceEditChannel, params: { id: connector.value?.id, deviceId: props.id, channelId: id } });
	} else {
		router.push({ name: routeNames.deviceSettingsEditChannel, params: { id: props.id, channelId: id } });
	}
};

const onDeviceCreated = (device: IDevice): void => {
	if (isConnectorSettingsRoute.value) {
		router.push({ name: routeNames.connectorSettingsEditDevice, params: { id: props.connectorId, deviceId: device.id } });
	} else {
		router.push({ name: routeNames.deviceSettings, params: { id: device.id } });
	}
};

onMounted((): void => {
	isMounted.value = true;
});

onUnmounted((): void => {
	if (device.value?.draft) {
		devicesStore.remove({ id: device.value.id });
	}
});

useMeta(() => ({
	title: t('meta.title', { device: useEntityTitle(deviceData.value.device).value }),
}));
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'view-device-settings';
</style>

<i18n>
{
  "en": {
    "meta": {
      "title": "Device settings: {device}"
    },
    "headings": {
      "add": "Add connector device",
      "edit": "Configure connector device",
      "configure": "Configure device"
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
