<template>
	<template v-if="connectorData !== null">
		<template v-if="isExtraSmallDevice || isMounted">
			<fb-layout-header-heading
				:heading="t('headings.configure')"
				:sub-heading="useEntityTitle(connectorData.connector).value"
			/>

			<template v-if="isExtraSmallDevice">
				<fb-layout-header-button
					:action-type="FbMenuItemTypes.VUE_LINK"
					:action="{ name: routeNames.connectorDetail, params: { id: props.id } }"
					small
					left
				>
					{{ t('buttons.close.title') }}
				</fb-layout-header-button>

				<fb-layout-header-button
					:action-type="FbMenuItemTypes.VUE_LINK"
					:action="{ name: routeNames.connectorDetail, params: { id: props.id } }"
					small
					right
				>
					{{ t('buttons.save.title') }}
				</fb-layout-header-button>
			</template>
		</template>

		<connector-settings-connector-settings
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			:connector-data="connectorData"
			@add-device="onAddDevice"
			@edit-device="onEditDevice"
		/>

		<fb-ui-content
			v-if="!isExtraSmallDevice"
			:pv="FbSizeTypes.MEDIUM"
			:ph="FbSizeTypes.MEDIUM"
			class="fb-devices-module-view-connector-settings__buttons"
		>
			<fb-ui-button
				:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
				:size="FbSizeTypes.MEDIUM"
				:loading="remoteFormResult === FbFormResultTypes.WORKING"
				:disabled="remoteFormResult !== FbFormResultTypes.NONE"
				uppercase
				class="fb-devices-module-view-connector-settings__buttons-save"
				@click="onSubmit"
			>
				{{ t('buttons.save.title') }}
			</fb-ui-button>

			<fb-ui-button
				:variant="FbUiButtonVariantTypes.LINK_DEFAULT"
				:size="FbSizeTypes.MEDIUM"
				:disabled="remoteFormResult !== FbFormResultTypes.NONE"
				uppercase
				class="fb-devices-module-view-connector-settings__buttons-close"
				@click="onClose"
			>
				{{ t('buttons.close.title') }}
			</fb-ui-button>
		</fb-ui-content>
	</template>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRouter } from 'vue-router';
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

import { useBreakpoints, useEntityTitle, useRoutesNames, useUuid } from '@/composables';
import {
	useChannelControls,
	useChannelProperties,
	useChannels,
	useConnectorControls,
	useConnectorProperties,
	useConnectors,
	useDeviceControls,
	useDeviceProperties,
	useDevices,
} from '@/models';
import {
	IChannelControl,
	IChannelProperty,
	IConnectorControl,
	IConnectorProperty,
	IDeviceControl,
	IDeviceProperty,
} from '@/models/types';
import { ConnectorSettingsConnectorSettings } from '@/components';
import { IChannelData, IConnectorData, IDeviceData, IViewConnectorSettingsProps } from '@/types';

const props = defineProps<IViewConnectorSettingsProps>();

const { t } = useI18n();
const router = useRouter();

const { isExtraSmallDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const { validate: validateUuid } = useUuid();

const connectorsStore = useConnectors();
const connectorControlsStore = useConnectorControls();
const connectorPropertiesStore = useConnectorProperties();
const devicesStore = useDevices();
const deviceControlsStore = useDeviceControls();
const devicePropertiesStore = useDeviceProperties();
const channelsStore = useChannels();
const channelControlsStore = useChannelControls();
const channelPropertiesStore = useChannelProperties();

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FbFormResultTypes>(FbFormResultTypes.NONE);

const isMounted = ref<boolean>(false);

const connectorData = computed<IConnectorData | null>((): IConnectorData | null => {
	if (validateUuid(props.id)) {
		const connector = connectorsStore.findById(props.id);

		if (connector === null) {
			return null;
		}

		return {
			connector,
			controls: orderBy<IConnectorControl>(
				connectorControlsStore.findForConnector(connector.id).filter((control) => !control.draft),
				[(v): string => v.name],
				['asc']
			),
			properties: orderBy<IConnectorProperty>(
				connectorPropertiesStore.findForConnector(connector.id).filter((control) => !control.draft),
				[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
				['asc']
			),
			devices: orderBy<IDeviceData>(
				devicesStore.findForConnector(connector.id).map((device): IDeviceData => {
					return {
						device,
						controls: orderBy<IDeviceControl>(
							deviceControlsStore.findForDevice(device.id).filter((control) => !control.draft),
							[(v): string => v.name],
							['asc']
						),
						properties: orderBy<IDeviceProperty>(
							devicePropertiesStore.findForDevice(device.id).filter((property) => !property.draft),
							[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
							['asc']
						),
						channels: orderBy<IChannelData>(
							channelsStore.findForDevice(device.id).map((channel): IChannelData => {
								return {
									channel,
									controls: orderBy<IChannelControl>(
										channelControlsStore.findForChannel(channel.id).filter((control) => !control.draft),
										[(v): string => v.name],
										['asc']
									),
									properties: orderBy<IChannelProperty>(
										channelPropertiesStore.findForChannel(channel.id).filter((property) => !property.draft),
										[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
										['asc']
									),
								};
							}),
							[(v): string => v.channel.name ?? v.channel.identifier, (v): string => v.channel.identifier],
							['asc']
						),
					};
				}),
				[(v): string => v.device.name ?? v.device.identifier, (v): string => v.device.identifier],
				['asc']
			),
		};
	}

	return null;
});

const onSubmit = (): void => {
	remoteFormSubmit.value = true;
};

const onClose = (): void => {
	router.push({ name: routeNames.connectorDetail, params: { id: props.id } });
};

const onAddDevice = (): void => {
	router.push({ name: routeNames.connectorSettingsAddDevice, params: { id: props.id } });
};

const onEditDevice = (id: string): void => {
	router.push({ name: routeNames.connectorSettingsEditDevice, params: { id: props.id, deviceId: id } });
};

onMounted((): void => {
	isMounted.value = true;
});

useMeta(() => ({
	title: t('meta.title', { connector: useEntityTitle(connectorData.value?.connector).value }),
}));
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'view-connector-settings';
</style>

<i18n>
{
  "en": {
    "meta": {
      "title": "Connector settings: {connector}"
    },
    "headings": {
      "configure": "Configure connector"
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
