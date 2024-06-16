<template>
	<div
		v-loading="isLoading || deviceData === null"
		:element-loading-text="t('texts.misc.loadingDevice')"
		class="flex flex-col overflow-hidden h-full"
	>
		<template v-if="deviceData !== null">
			<fb-app-bar-heading
				v-if="isXSDevice && isDetailRoute"
				teleport
			>
				<template #icon>
					<devices-device-icon :device="deviceData.device" />
				</template>

				<template #title>
					{{ useEntityTitle(deviceData.device).value }}
				</template>

				<template #subtitle>
					{{ deviceData.device.comment }}
				</template>
			</fb-app-bar-heading>

			<fb-app-bar-button
				v-if="isXSDevice && isDetailRoute"
				teleport
				:align="AppBarButtonAlignTypes.LEFT"
				small
				@click="onClose"
			>
				<el-icon>
					<fas-angle-left />
				</el-icon>
			</fb-app-bar-button>

			<fb-app-bar-button
				v-if="isXSDevice && isDetailRoute"
				teleport
				:align="AppBarButtonAlignTypes.RIGHT"
				small
				@click="onConfigure"
			>
				<span class="uppercase">{{ t('buttons.edit.title') }}</span>
			</fb-app-bar-button>

			<template v-if="!isXSDevice">
				<devices-device-toolbar
					:page="page"
					:total="devices.length"
					:edit-mode="editMode"
					@toggle-edit="onToggleEditMode"
					@previous="onPrevious"
					@next="onNext"
					@close="onClose"
				/>

				<devices-device-heading
					:device="deviceData.device"
					:edit-mode="editMode"
					@remove="onOpenRemove"
					@configure="onConfigure"
				/>

				<div
					v-loading="areChannelsLoading"
					:element-loading-text="t('texts.misc.loadingChannels')"
					class="flex-grow overflow-hidden"
				>
					<device-default-device-detail
						:loading="isLoading"
						:channels-loading="areChannelsLoading"
						:device-data="deviceData"
						:edit-mode="editMode"
						@add-channel-parameter="onAddChannelParameter"
					/>
				</div>
			</template>

			<div
				v-else
				class="h-full"
			>
				<fb-expandable-box :show="isDetailRoute">
					<device-default-device-detail
						v-loading="areChannelsLoading"
						:element-loading-text="t('texts.misc.loadingChannels')"
						:loading="isLoading"
						:channels-loading="areChannelsLoading"
						:device-data="deviceData"
						:edit-mode="editMode"
						@add-channel-parameter="onAddChannelParameter"
					/>
				</fb-expandable-box>

				<fb-expandable-box :show="!isDetailRoute">
					<suspense>
						<div class="flex-grow overflow-hidden h-full">
							<view-error :type="isSettingsRoute ? 'device' : isChannelSettingsRoute ? 'channel' : null">
								<router-view />
							</view-error>
						</div>
					</suspense>
				</fb-expandable-box>
			</div>

			<router-view
				v-if="!isXSDevice"
				v-slot="{ Component }"
			>
				<el-drawer
					v-model="showSettings"
					:show-close="false"
					:size="'40%'"
					:with-header="false"
					@closed="onCloseSettings"
				>
					<div class="flex flex-col h-full">
						<fb-app-bar menu-button-hidden>
							<template #heading>
								<fb-app-bar-heading>
									<template #icon>
										<fas-gears />
									</template>

									<template #title>
										{{ t('headings.devices.configuration') }}
									</template>

									<template #subtitle>
										{{ useEntityTitle(deviceData.device).value }}
									</template>
								</fb-app-bar-heading>
							</template>

							<template #button-right>
								<fb-app-bar-button
									:align="AppBarButtonAlignTypes.RIGHT"
									@click="showSettings = false"
								>
									<el-icon>
										<fas-xmark />
									</el-icon>
								</fb-app-bar-button>
							</template>
						</fb-app-bar>

						<suspense>
							<div class="flex-grow overflow-hidden">
								<view-error :type="isSettingsRoute ? 'device' : isChannelSettingsRoute ? 'channel' : null">
									<component
										:is="Component"
										:key="route.path"
									/>
								</view-error>
							</div>
						</suspense>
					</div>
				</el-drawer>
			</router-view>
		</template>
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { RouteRecordName, useRoute, useRouter } from 'vue-router';
import get from 'lodash/get';
import { orderBy } from 'natural-orderby';
import { ElDrawer, ElIcon, ElMessageBox, vLoading } from 'element-plus';

import { FasAngleLeft, FasGears, FasXmark } from '@fastybird/web-ui-icons';
import { FbAppBar, FbAppBarButton, FbAppBarHeading, FbExpandableBox, AppBarButtonAlignTypes } from '@fastybird/web-ui-library';

import { useBreakpoints, useEntityTitle, useFlashMessage, useRoutesNames, useUuid } from '../composables';
import { useChannelControls, useChannelProperties, useChannels, useDeviceControls, useDeviceProperties, useDevices } from '../models';
import { IChannelControl, IChannelProperty, IDevice, IDeviceControl, IDeviceProperty } from '../models/types';
import { DeviceDefaultDeviceDetail, DevicesDeviceHeading, DevicesDeviceToolbar, DevicesDeviceIcon, ViewError } from '../components';
import { ApplicationError } from '../errors';
import { IChannelData, IDeviceData, IViewDeviceDetailProps } from '../types';

defineOptions({
	name: 'ViewDeviceDetail',
});

const props = defineProps<IViewDeviceDetailProps>();

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const { meta } = useMeta({});

const { isXSDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const { validate: validateUuid } = useUuid();
const flashMessage = useFlashMessage();

const devicesStore = useDevices();
const deviceControlsStore = useDeviceControls();
const devicePropertiesStore = useDeviceProperties();
const channelsStore = useChannels();
const channelControlsStore = useChannelControls();
const channelPropertiesStore = useChannelProperties();

if (!validateUuid(props.id)) {
	throw new Error('Device identifier is not valid');
}

const isLoading = computed<boolean>((): boolean => {
	if (devicesStore.getting(props.id)) {
		return true;
	}

	if (devicesStore.findById(props.id)) {
		return false;
	}

	return devicesStore.fetching(props.connectorId ?? null);
});
const areChannelsLoading = computed<boolean>((): boolean => {
	if (channelsStore.fetching(props.id)) {
		return true;
	}

	if (channelsStore.firstLoadFinished(props.id)) {
		return false;
	}

	return channelsStore.fetching();
});

const editMode = ref<boolean>(false);
const showSettings = ref<boolean>(false);

const isDetailRoute = computed<boolean>((): boolean => route.name === routeNames.deviceDetail);
const isSettingsRoute = computed<boolean>((): boolean => route.name === routeNames.deviceSettings);
const isChannelSettingsRoute = computed<boolean>(
	(): boolean => route.name === routeNames.deviceSettingsAddChannel || route.name === routeNames.deviceSettingsEditChannel
);

const devices = computed<IDevice[]>((): IDevice[] => {
	return Object.values(devicesStore.data);
});

const deviceData = computed<IDeviceData | null>((): IDeviceData | null => {
	const device = devicesStore.findById(props.id);

	if (device === null) {
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
});

const page = computed<number>((): number => {
	const index = Object.values(devicesStore.data).findIndex(({ id }) => id === props.id);

	if (index !== -1) {
		return index + 1;
	}

	return 0;
});

const onPrevious = (): void => {
	const index = devices.value.findIndex(({ id }) => id === props.id) - 1;

	if (index <= devices.value.length && index >= 0 && typeof devices.value[index] !== 'undefined') {
		router.push({
			name: routeNames.deviceDetail,
			params: {
				id: devices.value[index].id,
			},
		});
	}
};

const onNext = (): void => {
	const index = devices.value.findIndex(({ id }) => id === props.id) + 1;

	if (index <= devices.value.length && index >= 0 && typeof devices.value[index] !== 'undefined') {
		router.push({
			name: routeNames.deviceDetail,
			params: {
				id: devices.value[index].id,
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

const onOpenRemove = (): void => {
	if (deviceData.value === null) {
		return;
	}

	ElMessageBox.confirm(t('messages.devices.confirmRemove', { device: useEntityTitle(deviceData.value.device).value }), t('headings.devices.remove'), {
		confirmButtonText: t('buttons.yes.title'),
		cancelButtonText: t('buttons.no.title'),
		type: 'warning',
	})
		.then((): void => {
			router.push({ name: routeNames.devices }).then(async (): Promise<void> => {
				try {
					await devicesStore.remove({ id: props.id });
				} catch (e: any) {
					const errorMessage = t('messages.devices.notRemoved', {
						device: useEntityTitle(deviceData.value?.device).value,
					});

					if (get(e, 'exception', null) !== null) {
						flashMessage.exception(get(e, 'exception', null), errorMessage);
					} else {
						flashMessage.error(errorMessage);
					}
				}
			});
		})
		.catch(() => {
			flashMessage.info(
				t('messages.devices.removeCanceled', {
					connector: useEntityTitle(deviceData.value!.device).value,
				})
			);
		});
};

const onConfigure = (): void => {
	router.push({ name: routeNames.deviceSettings, params: { id: props.id } });
};

const onCloseSettings = (): void => {
	router.push({ name: routeNames.deviceDetail, params: { id: props.id } });
};

const onAddChannelParameter = (id: string): void => {
	const channel = channelsStore.findById(id);

	if (channel === null) {
		return;
	}

	// TODO: Add channel parameter
};

onBeforeMount(async (): Promise<void> => {
	fetchDevice(props.id).catch((e) => {
		if (get(e, 'exception.response.status', 0) === 404) {
			throw new ApplicationError('Device Not Found', e, { statusCode: 404, message: 'Device Not Found' });
		} else {
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		}
	});

	if (!isLoading.value && devicesStore.findById(props.id) === null) {
		throw new ApplicationError('Device Not Found', null, { statusCode: 404, message: 'Device Not Found' });
	}

	if (deviceData.value) {
		fetchChannels(deviceData.value.device).catch((e) => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Device Not Found', e, { statusCode: 404, message: 'Device Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});
	}

	if (
		route.name === routeNames.deviceSettings ||
		route.name === routeNames.deviceSettingsAddChannel ||
		route.name === routeNames.deviceSettingsEditChannel
	) {
		editMode.value = true;

		showSettings.value = true;
	}
});

const fetchDevice = async (id: string): Promise<void> => {
	if (!isLoading.value && !devicesStore.firstLoadFinished) {
		await devicesStore.get({ id });
	}
};

const fetchChannels = async (device: IDevice): Promise<void> => {
	if (!areChannelsLoading.value && !channelsStore.firstLoadFinished(device.id)) {
		await channelsStore.fetch({ device });
	}
};

watch(
	(): RouteRecordName | null | undefined => route.name,
	(val: RouteRecordName | null | undefined): void => {
		if (val === routeNames.deviceSettings || val === routeNames.deviceSettingsAddChannel || val === routeNames.deviceSettingsEditChannel) {
			editMode.value = true;

			showSettings.value = true;
		} else {
			editMode.value = false;

			showSettings.value = false;
		}
	}
);

watch(
	(): boolean => isLoading.value,
	(val: boolean): void => {
		if (!val && deviceData.value === null) {
			throw new ApplicationError('Device Not Found', null, { statusCode: 404, message: 'Device Not Found' });
		}
	}
);

watch(
	(): IDeviceData | null => deviceData.value,
	(val: IDeviceData | null): void => {
		if (val !== null) {
			meta.title = t('meta.devices.detail.title', { device: useEntityTitle(val.device).value });

			fetchChannels(val.device).catch((e) => {
				if (get(e, 'exception.response.status', 0) === 404) {
					throw new ApplicationError('Device Not Found', null, { statusCode: 404, message: 'Device Not Found' });
				} else {
					throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
				}
			});
		}

		if (!isLoading.value && val === null) {
			throw new ApplicationError('Device Not Found', null, { statusCode: 404, message: 'Device Not Found' });
		}
	}
);
</script>
