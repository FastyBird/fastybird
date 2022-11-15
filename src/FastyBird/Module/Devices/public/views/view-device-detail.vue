<template>
	<template v-if="deviceData !== null">
		<template v-if="!isExtraSmallDevice">
			<devices-device-toolbar
				:page="page"
				:total="props.devices.length"
				:edit-mode="editMode"
				@toggle-edit="onToggleEditMode"
				@previous="onPrevious"
				@next="onNext"
				@close="onClose"
			/>

			<devices-device-heading
				:device="deviceData.device"
				:edit-mode="editMode"
				@remove="onOpenView(ViewDeviceDetailViewTypes.REMOVE)"
				@configure="onConfigure"
			/>
		</template>

		<template v-if="!isExtraSmallDevice">
			<device-default-device-detail
				:device-data="deviceData"
				:edit-mode="editMode"
			/>
		</template>
		<template v-else>
			<fb-layout-expandable-box :show="isDetailRoute">
				<device-default-device-detail
					:device-data="deviceData"
					:edit-mode="editMode"
				/>
			</fb-layout-expandable-box>

			<fb-layout-expandable-box :show="!isDetailRoute">
				<suspense>
					<router-view :connector-id="deviceData?.device.connector.id" />

					<template #fallback>
						<fb-ui-component-loading />
					</template>
				</suspense>
			</fb-layout-expandable-box>
		</template>

		<router-view v-slot="{ Component }">
			<fb-layout-off-canvas
				v-if="!isExtraSmallDevice"
				:show="isPartialSettingsRoute"
				@close="onCloseSettings"
			>
				<div class="fb-devices-module-view-device-detail__setting">
					<fb-layout-header menu-button-hidden>
						<template #button-right>
							<fb-layout-header-icon
								:teleport="false"
								right
							>
								<font-awesome-icon icon="cogs" />
							</fb-layout-header-icon>
						</template>
					</fb-layout-header>

					<suspense>
						<component
							:is="Component"
							:connector-id="deviceData?.device.connector.id"
						/>

						<template #fallback>
							<fb-ui-component-loading />
						</template>
					</suspense>
				</div>
			</fb-layout-off-canvas>
		</router-view>

		<template v-if="isExtraSmallDevice">
			<fb-layout-header-icon right>
				<devices-device-icon :device="deviceData.device" />
			</fb-layout-header-icon>

			<template v-if="isDetailRoute">
				<fb-layout-header-heading
					:heading="useEntityTitle(deviceData.device).value"
					:sub-heading="deviceData.device.comment"
				/>

				<fb-layout-header-button
					:action-type="FbMenuItemTypes.VUE_LINK"
					:action="{ name: routeNames.devices }"
					small
					left
				>
					<template #icon>
						<font-awesome-icon icon="angle-left" />
					</template>
				</fb-layout-header-button>

				<fb-layout-header-button
					:action-type="FbMenuItemTypes.VUE_LINK"
					:action="{ name: routeNames.deviceSettings, params: { id: props.id } }"
					small
					right
				>
					{{ t('buttons.edit.title') }}
				</fb-layout-header-button>
			</template>
		</template>

		<device-settings-device-remove
			v-if="activeView === ViewDeviceDetailViewTypes.REMOVE"
			:device="deviceData.device"
			:call-remove="false"
			@close="onCloseView"
			@confirmed="onRemoveConfirmed"
		/>
	</template>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRoute, useRouter } from 'vue-router';
import get from 'lodash/get';
import { orderBy } from 'natural-orderby';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
	FbLayoutExpandableBox,
	FbLayoutHeader,
	FbLayoutHeaderButton,
	FbLayoutHeaderHeading,
	FbLayoutHeaderIcon,
	FbLayoutOffCanvas,
	FbUiComponentLoading,
	FbMenuItemTypes,
} from '@fastybird/web-ui-library';

import { useBreakpoints, useEntityTitle, useFlashMessage, useRoutesNames, useUuid } from '@/composables';
import { useChannelControls, useChannelProperties, useChannels, useDeviceControls, useDeviceProperties, useDevices } from '@/models';
import { IChannelControl, IChannelProperty, IDeviceAttribute, IDeviceControl, IDeviceProperty } from '@/models/types';
import { DeviceDefaultDeviceDetail, DevicesDeviceHeading, DevicesDeviceToolbar, DevicesDeviceIcon, DeviceSettingsDeviceRemove } from '@/components';
import { ApplicationError } from '@/errors';
import { IChannelData, IDeviceData, IViewDeviceDetailProps } from '@/types';
import useDeviceAttributes from '@/models/devices-attributes';
import { ViewDeviceDetailViewTypes } from '@/views/view-device-detail.types';

const props = defineProps<IViewDeviceDetailProps>();

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const { isExtraSmallDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const { validate: validateUuid } = useUuid();
const flashMessage = useFlashMessage();

const devicesStore = useDevices();
const deviceControlsStore = useDeviceControls();
const devicePropertiesStore = useDeviceProperties();
const deviceAttributesStore = useDeviceAttributes();
const channelsStore = useChannels();
const channelControlsStore = useChannelControls();
const channelPropertiesStore = useChannelProperties();

const editMode = ref<boolean>(false);

const isLoading = computed<boolean>((): boolean => devicesStore.fetching || channelsStore.fetching(props.id));

const isDetailRoute = computed<boolean>((): boolean => route.name === routeNames.deviceDetail);
const isPartialSettingsRoute = computed<boolean>((): boolean => {
	return (
		route.matched.find(
			(matched) =>
				matched.name === routeNames.deviceSettings ||
				matched.name === routeNames.deviceSettingsAddChannel ||
				matched.name === routeNames.deviceSettingsEditChannel
		) !== undefined
	);
});

const activeView = ref<ViewDeviceDetailViewTypes>(ViewDeviceDetailViewTypes.NONE);

const deviceData = computed<IDeviceData | null>((): IDeviceData | null => {
	if (validateUuid(props.id)) {
		const device = props.devices.find((device) => device.id === props.id);

		if (device === undefined) {
			return null;
		}

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
			attributes: orderBy<IDeviceAttribute>(
				deviceAttributesStore.findForDevice(device.id).filter((attribute) => !attribute.draft),
				[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
				['asc']
			),
			channels: orderBy<IChannelData>(
				channelsStore
					.findForDevice(device.id)
					.filter((channel) => !channel.draft)
					.map((channel): IChannelData => {
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
	}

	return null;
});

const page = computed<number>((): number => {
	const index = props.devices.findIndex(({ id }) => id === props.id);

	if (index !== -1) {
		return index + 1;
	}

	return 0;
});

const onPrevious = (): void => {
	const index = props.devices.findIndex(({ id }) => id === props.id) - 1;

	if (index <= props.devices.length && index >= 0 && typeof props.devices[index] !== 'undefined') {
		router.push({
			name: routeNames.deviceDetail,
			params: {
				id: props.devices[index].id,
			},
		});
	}
};

const onNext = (): void => {
	const index = props.devices.findIndex(({ id }) => id === props.id) + 1;

	if (index <= props.devices.length && index >= 0 && typeof props.devices[index] !== 'undefined') {
		router.push({
			name: routeNames.deviceDetail,
			params: {
				id: props.devices[index].id,
			},
		});
	}
};

const onClose = (): void => {
	router.push({ name: routeNames.devices });
};

const onToggleEditMode = (): void => {
	editMode.value = !editMode.value;
};

const onOpenView = (viewType: ViewDeviceDetailViewTypes): void => {
	activeView.value = viewType;
};

const onCloseView = (): void => {
	activeView.value = ViewDeviceDetailViewTypes.NONE;
};

const onRemoveConfirmed = (): void => {
	router.push({ name: routeNames.devices }).then(async (): Promise<void> => {
		try {
			await devicesStore.remove({ id: props.id });
		} catch (e: any) {
			const errorMessage = t('messages.notRemoved', {
				device: useEntityTitle(deviceData.value?.device).value,
			});

			if (get(e, 'exception', null) !== null) {
				flashMessage.exception(get(e, 'exception', null), errorMessage);
			} else {
				flashMessage.error(errorMessage);
			}
		}
	});
};

const onConfigure = (): void => {
	router.push({ name: routeNames.deviceSettings, params: { id: props.id } });
};

const onCloseSettings = (): void => {
	router.push({ name: routeNames.deviceDetail, params: { id: props.id } });
};

onBeforeMount(async (): Promise<void> => {
	if (!isLoading.value && !devicesStore.firstLoadFinished) {
		try {
			await devicesStore.get({ id: props.id, withChannels: true });
		} catch (e: any) {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Device Not Found', e, { statusCode: 404, message: 'Device Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		}
	} else if (!isLoading.value && !channelsStore.firstLoadFinished(props.id) && deviceData.value !== null) {
		try {
			await channelsStore.fetch({ device: deviceData.value?.device });
		} catch (e: any) {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Device Not Found', e, { statusCode: 404, message: 'Device Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		}
	} else if (devicesStore.findById(props.id) === null) {
		throw new ApplicationError('Device Not Found', null, { statusCode: 404, message: 'Device Not Found' });
	}

	if (
		route.name === routeNames.deviceSettings ||
		route.name === routeNames.deviceSettingsAddChannel ||
		route.name === routeNames.deviceSettingsEditChannel
	) {
		editMode.value = true;
	}
});

useMeta(() => ({
	title: t('meta.title', { device: useEntityTitle(deviceData.value?.device).value }),
}));
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'view-device-detail';
</style>

<i18n>
{
  "en": {
    "meta": {
      "title": "Device: {device}"
    },
    "messages": {
      "notRemoved": "Device {device} couldn't be removed."
    },
    "buttons": {
      "edit": {
        "title": "Edit"
      }
    }
  }
}
</i18n>
