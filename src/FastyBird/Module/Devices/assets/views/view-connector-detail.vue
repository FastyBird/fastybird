<template>
	<div
		v-loading="isLoading || connectorData === null"
		:element-loading-text="t('texts.misc.loadingConnector')"
		class="flex flex-col overflow-hidden h-full"
	>
		<template v-if="connectorData !== null">
			<fb-app-bar-heading
				v-if="isXSDevice && isDetailRoute"
				teleport
			>
				<template #icon>
					<connectors-connector-icon :connector="connectorData.connector" />
				</template>

				<template #title>
					{{ useEntityTitle(connectorData.connector).value }}
				</template>

				<template #subtitle>
					{{ connectorData.connector.comment }}
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
				<connectors-connector-toolbar
					:page="page"
					:total="connectors.length"
					:edit-mode="editMode"
					@toggle-edit="onToggleEditMode"
					@previous="onPrevious"
					@next="onNext"
					@close="onClose"
				/>

				<connectors-connector-heading
					:connector="connectorData.connector"
					:edit-mode="editMode"
					@remove="onRemove"
					@configure="onConfigure"
				/>

				<div
					v-loading="areDevicesLoading"
					:element-loading-text="t('texts.misc.loadingDevices')"
					class="flex-grow overflow-hidden"
				>
					<connector-default-connector-detail
						:loading="isLoading"
						:devices-loading="areDevicesLoading"
						:connector-data="connectorData"
						:edit-mode="editMode"
					/>
				</div>
			</template>

			<div
				v-else
				class="h-full"
			>
				<fb-expandable-box :show="isDetailRoute">
					<connector-default-connector-detail
						v-loading="areDevicesLoading"
						:element-loading-text="t('texts.misc.loadingDevices')"
						:loading="isLoading"
						:devices-loading="areDevicesLoading"
						:connector-data="connectorData"
						:edit-mode="editMode"
					/>
				</fb-expandable-box>

				<fb-expandable-box :show="!isDetailRoute">
					<suspense>
						<div class="flex-grow overflow-hidden h-full">
							<view-error :type="isSettingsRoute ? 'connector' : isDeviceSettingsRoute ? 'device' : isChannelSettingsRoute ? 'channel' : null">
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
										{{ t('headings.connectors.configuration') }}
									</template>

									<template #subtitle>
										{{ useEntityTitle(connectorData.connector).value }}
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
								<view-error :type="isSettingsRoute ? 'connector' : isDeviceSettingsRoute ? 'device' : isChannelSettingsRoute ? 'channel' : null">
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
import get from 'lodash.get';
import { orderBy } from 'natural-orderby';
import { ElDrawer, ElIcon, ElMessageBox, vLoading } from 'element-plus';

import { FasAngleLeft, FasGears, FasXmark } from '@fastybird/web-ui-icons';
import { FbAppBar, FbAppBarButton, FbAppBarHeading, FbExpandableBox, AppBarButtonAlignTypes } from '@fastybird/web-ui-library';

import { useBreakpoints, useEntityTitle, useFlashMessage, useRoutesNames, useUuid } from '../composables';
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
} from '../models';
import {
	IChannelControl,
	IChannelProperty,
	IConnector,
	IConnectorControl,
	IConnectorProperty,
	IDeviceControl,
	IDeviceProperty,
} from '../models/types';
import {
	ConnectorDefaultConnectorDetail,
	ConnectorsConnectorHeading,
	ConnectorsConnectorToolbar,
	ConnectorsConnectorIcon,
	ViewError,
} from '../components';
import { ApplicationError } from '../errors';
import { IChannelData, IConnectorData, IDeviceData } from '../types';
import { IViewConnectorDetailProps } from './view-connector-detail.types';

defineOptions({
	name: 'ViewConnectorDetail',
});

const props = defineProps<IViewConnectorDetailProps>();

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const { meta } = useMeta({});

const { isXSDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const { validate: validateUuid } = useUuid();
const flashMessage = useFlashMessage();

const connectorsStore = useConnectors();
const connectorControlsStore = useConnectorControls();
const connectorPropertiesStore = useConnectorProperties();
const devicesStore = useDevices();
const deviceControlsStore = useDeviceControls();
const devicePropertiesStore = useDeviceProperties();
const channelsStore = useChannels();
const channelControlsStore = useChannelControls();
const channelPropertiesStore = useChannelProperties();

if (!validateUuid(props.id)) {
	throw new Error('Connector identifier is not valid');
}

const isLoading = computed<boolean>((): boolean => {
	if (connectorsStore.getting(props.id)) {
		return true;
	}

	if (connectorsStore.findById(props.id)) {
		return false;
	}

	return connectorsStore.fetching();
});
const areDevicesLoading = computed<boolean>((): boolean => {
	if (devicesStore.fetching(props.id)) {
		return true;
	}

	if (devicesStore.firstLoadFinished(props.id)) {
		return false;
	}

	return devicesStore.fetching();
});

const editMode = ref<boolean>(false);
const showSettings = ref<boolean>(false);

const isDetailRoute = computed<boolean>((): boolean => route.name === routeNames.connectorDetail);
const isSettingsRoute = computed<boolean>((): boolean => route.name === routeNames.connectorSettings);
const isDeviceSettingsRoute = computed<boolean>(
	(): boolean => route.name === routeNames.connectorSettingsAddDevice || route.name === routeNames.connectorSettingsEditDevice
);
const isChannelSettingsRoute = computed<boolean>(
	(): boolean => route.name === routeNames.connectorSettingsEditDeviceAddChannel || route.name === routeNames.connectorSettingsEditDeviceEditChannel
);

const connectors = computed<IConnector[]>((): IConnector[] => {
	return connectorsStore.findAll();
});

const connectorData = computed<IConnectorData | null>((): IConnectorData | null => {
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
			devicesStore
				.findForConnector(connector.id)
				.filter((device) => !device.draft)
				.map((device): IDeviceData => {
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
});

const page = computed<number>((): number => {
	const index = connectorsStore.findAll().findIndex(({ id }) => id === props.id);

	if (index !== -1) {
		return index + 1;
	}

	return 0;
});

const onPrevious = (): void => {
	const index = connectors.value.findIndex(({ id }) => id === props.id) - 1;

	if (index <= connectors.value.length && index >= 0 && typeof connectors.value[index] !== 'undefined') {
		router.push({
			name: routeNames.connectorDetail,
			params: {
				id: connectors.value[index].id,
			},
		});
	}
};

const onNext = (): void => {
	const index = connectors.value.findIndex(({ id }) => id === props.id) + 1;

	if (index <= connectors.value.length && index >= 0 && typeof connectors.value[index] !== 'undefined') {
		router.push({
			name: routeNames.connectorDetail,
			params: {
				id: connectors.value[index].id,
			},
		});
	}
};

const onClose = (): void => {
	router.push({ name: routeNames.connectors });
};

const onToggleEditMode = (): void => {
	editMode.value = !editMode.value;
};

const onRemove = (): void => {
	if (connectorData.value === null) {
		return;
	}

	ElMessageBox.confirm(
		t('messages.connectors.confirmRemove', { connector: useEntityTitle(connectorData.value!.connector).value }),
		t('headings.connectors.remove'),
		{
			confirmButtonText: t('buttons.yes.title'),
			cancelButtonText: t('buttons.no.title'),
			type: 'warning',
		}
	)
		.then((): void => {
			router.push({ name: routeNames.connectors }).then(async (): Promise<void> => {
				try {
					await devicesStore.remove({ id: props.id });
				} catch (e: any) {
					const errorMessage = t('messages.connectors.notRemoved', {
						connector: useEntityTitle(connectorData.value!.connector).value,
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
				t('messages.connectors.removeCanceled', {
					connector: useEntityTitle(connectorData.value!.connector).value,
				})
			);
		});
};

const onConfigure = (): void => {
	router.push({ name: routeNames.connectorSettings, params: { id: props.id } });
};

const onCloseSettings = (): void => {
	router.push({ name: routeNames.connectorDetail, params: { id: props.id } });
};

onBeforeMount(async (): Promise<void> => {
	fetchConnector(props.id)
		.then((): void => {
			if (!isLoading.value && connectorsStore.findById(props.id) === null) {
				throw new ApplicationError('Connector Not Found', null, { statusCode: 404, message: 'Connector Not Found' });
			}
		})
		.catch((e: any): void => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});

	fetchDevices(props.id).catch((e: any): void => {
		if (get(e, 'exception.response.status', 0) === 404) {
			throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
		} else {
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		}
	});

	if (
		route.name === routeNames.connectorSettings ||
		route.name === routeNames.connectorSettingsAddDevice ||
		route.name === routeNames.connectorSettingsEditDevice ||
		route.name === routeNames.connectorSettingsEditDeviceAddChannel ||
		route.name === routeNames.connectorSettingsEditDeviceEditChannel
	) {
		editMode.value = true;

		showSettings.value = true;
	}
});

const fetchConnector = async (id: IConnector['id']): Promise<void> => {
	await connectorsStore.get({ id, refresh: !connectorsStore.firstLoadFinished() });

	const connector = connectorsStore.findById(id);

	if (connector) {
		await connectorPropertiesStore.fetch({ connector, refresh: false });
		await connectorControlsStore.fetch({ connector, refresh: false });
	}
};

const fetchDevices = async (connectorId: IConnector['id']): Promise<void> => {
	await devicesStore.fetch({ connectorId, refresh: !devicesStore.firstLoadFinished(connectorId) });

	const devices = devicesStore.findForConnector(connectorId);

	for (const device of devices) {
		await devicePropertiesStore.fetch({ device, refresh: false });
		await deviceControlsStore.fetch({ device, refresh: false });
	}
};

watch(
	(): RouteRecordName | null | undefined => route.name,
	(val: RouteRecordName | null | undefined): void => {
		if (
			val === routeNames.connectorSettings ||
			val === routeNames.connectorSettingsAddDevice ||
			val === routeNames.connectorSettingsEditDevice ||
			val === routeNames.connectorSettingsEditDeviceAddChannel ||
			val === routeNames.connectorSettingsEditDeviceEditChannel
		) {
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
		if (!val && connectorData.value === null) {
			throw new ApplicationError('Connector Not Found', null, { statusCode: 404, message: 'Connector Not Found' });
		}
	}
);

watch(
	(): IConnectorData | null => connectorData.value,
	(val: IConnectorData | null): void => {
		if (val !== null) {
			meta.title = t('meta.connectors.detail.title', { connector: useEntityTitle(val.connector).value });
		}

		if (!isLoading.value && val === null) {
			throw new ApplicationError('Connector Not Found', null, { statusCode: 404, message: 'Connector Not Found' });
		}
	}
);

watch(
	(): string => props.id,
	async (val: string): Promise<void> => {
		fetchConnector(val).catch((e) => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});

		fetchDevices(val).catch((e) => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});
	}
);
</script>
