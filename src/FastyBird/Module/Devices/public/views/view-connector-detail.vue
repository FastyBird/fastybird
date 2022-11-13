<template>
	<template v-if="connectorData !== null">
		<template v-if="!isExtraSmallDevice">
			<connectors-connector-toolbar
				:page="page"
				:total="props.connectors.length"
				:edit-mode="editMode"
				@toggle-edit="onToggleEditMode"
				@previous="onPrevious"
				@next="onNext"
				@close="onClose"
			/>

			<connectors-connector-heading
				:connector="connectorData.connector"
				:edit-mode="editMode"
				@remove="onOpenView(ViewTypes.REMOVE)"
				@configure="onConfigure"
			/>
		</template>

		<template v-if="!isExtraSmallDevice">
			<connector-default-connector-detail :connector-data="connectorData" />
		</template>
		<template v-else>
			<fb-layout-expandable-box :show="isDetailRoute">
				<connector-default-connector-detail :connector-data="connectorData" />
			</fb-layout-expandable-box>

			<fb-layout-expandable-box :show="!isDetailRoute">
				<suspense>
					<router-view />

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
				<div class="fb-devices-module-view-connector-detail__setting">
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
						<component :is="Component" />

						<template #fallback>
							<fb-ui-component-loading />
						</template>
					</suspense>
				</div>
			</fb-layout-off-canvas>
		</router-view>

		<template v-if="isExtraSmallDevice">
			<fb-layout-header-icon right>
				<connectors-connector-icon :connector="connectorData.connector" />
			</fb-layout-header-icon>

			<template v-if="isDetailRoute">
				<fb-layout-header-heading
					:heading="useEntityTitle(connectorData.connector).value"
					:sub-heading="connectorData.connector.comment"
				/>

				<fb-layout-header-button
					:action-type="FbMenuItemTypes.VUE_LINK"
					:action="{ name: routeNames.connectors }"
					small
					left
				>
					<template #icon>
						<font-awesome-icon icon="angle-left" />
					</template>
				</fb-layout-header-button>

				<fb-layout-header-button
					:action-type="FbMenuItemTypes.VUE_LINK"
					:action="{ name: routeNames.connectorSettings, params: { id: props.id } }"
					small
					right
				>
					{{ t('buttons.edit.title') }}
				</fb-layout-header-button>
			</template>
		</template>

		<connector-settings-connector-remove
			v-if="activeView === ViewTypes.REMOVE"
			:connector="connectorData.connector"
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
	IConnector,
	IConnectorControl,
	IConnectorProperty,
	IDeviceAttribute,
	IDeviceControl,
	IDeviceProperty,
} from '@/models/types';
import {
	ConnectorDefaultConnectorDetail,
	ConnectorsConnectorHeading,
	ConnectorsConnectorToolbar,
	ConnectorsConnectorIcon,
	ConnectorSettingsConnectorRemove,
} from '@/components';
import { ApplicationError } from '@/errors';
import { IChannelData, IConnectorData, IDeviceData } from '@/types';
import useDeviceAttributes from '@/models/devices-attributes';

enum ViewTypes {
	NONE = 'none',
	REMOVE = 'remove',
}

interface IViewConnectorProps {
	id: string;
	deviceId?: string;
	channelId?: string;
	connectors: IConnector[];
}

const props = defineProps<IViewConnectorProps>();

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const { isExtraSmallDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const { validate: validateUuid } = useUuid();
const flashMessage = useFlashMessage();

const connectorsStore = useConnectors();
const connectorControlsStore = useConnectorControls();
const connectorPropertiesStore = useConnectorProperties();
const devicesStore = useDevices();
const deviceControlsStore = useDeviceControls();
const devicePropertiesStore = useDeviceProperties();
const deviceAttributesStore = useDeviceAttributes();
const channelsStore = useChannels();
const channelControlsStore = useChannelControls();
const channelPropertiesStore = useChannelProperties();

const editMode = ref<boolean>(false);

const isLoading = computed<boolean>((): boolean => connectorsStore.fetching || devicesStore.fetching);

const isDetailRoute = computed<boolean>((): boolean => route.name === routeNames.connectorDetail);
const isPartialSettingsRoute = computed<boolean>((): boolean => {
	return (
		route.matched.find((matched) => {
			return (
				matched.name === routeNames.connectorSettings ||
				matched.name === routeNames.connectorSettingsAddDevice ||
				matched.name === routeNames.connectorSettingsEditDevice ||
				matched.name === routeNames.connectorSettingsEditDeviceAddChannel ||
				matched.name === routeNames.connectorSettingsEditDeviceEditChannel
			);
		}) !== undefined
	);
});

const activeView = ref<ViewTypes>(ViewTypes.NONE);

const connectorData = computed<IConnectorData | null>((): IConnectorData | null => {
	if (validateUuid(props.id)) {
		const connector = props.connectors.find((connector) => connector.id === props.id);

		if (connector === undefined) {
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
							attributes: orderBy<IDeviceAttribute>(
								deviceAttributesStore.findForDevice(device.id).filter((attribute) => !attribute.draft),
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

const page = computed<number>((): number => {
	const index = props.connectors.findIndex(({ id }) => id === props.id);

	if (index !== -1) {
		return index + 1;
	}

	return 0;
});

const onPrevious = (): void => {
	const index = props.connectors.findIndex(({ id }) => id === props.id) - 1;

	if (index <= props.connectors.length && index >= 0 && typeof props.connectors[index] !== 'undefined') {
		router.push({
			name: routeNames.connectorDetail,
			params: {
				id: props.connectors[index].id,
			},
		});
	}
};

const onNext = (): void => {
	const index = props.connectors.findIndex(({ id }) => id === props.id) + 1;

	if (index <= props.connectors.length && index >= 0 && typeof props.connectors[index] !== 'undefined') {
		router.push({
			name: routeNames.connectorDetail,
			params: {
				id: props.connectors[index].id,
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

const onOpenView = (viewType: ViewTypes): void => {
	activeView.value = viewType;
};

const onCloseView = (): void => {
	activeView.value = ViewTypes.NONE;
};

const onRemoveConfirmed = (): void => {
	router.push({ name: routeNames.connectors }).then(async (): Promise<void> => {
		try {
			await devicesStore.remove({ id: props.id });
		} catch (e: any) {
			const errorMessage = t('messages.notRemoved', {
				device: useEntityTitle(connectorData.value?.connector).value,
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
	router.push({ name: routeNames.connectorSettings, params: { id: props.id } });
};

const onCloseSettings = (): void => {
	router.push({ name: routeNames.connectorDetail, params: { id: props.id } });
};

onBeforeMount(async (): Promise<void> => {
	if (!isLoading.value && !connectorsStore.firstLoadFinished) {
		try {
			await connectorsStore.get({ id: props.id });
		} catch (e: any) {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		}
	} else if (!isLoading.value && !devicesStore.firstLoadFinished) {
		try {
			await devicesStore.fetch({});
		} catch (e: any) {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		}
	} else if (connectorsStore.findById(props.id) === null) {
		throw new ApplicationError('Connector Not Found', null, { statusCode: 404, message: 'Connector Not Found' });
	}

	if (
		route.name === routeNames.connectorSettings ||
		route.name === routeNames.connectorSettingsAddDevice ||
		route.name === routeNames.connectorSettingsEditDevice ||
		route.name === routeNames.connectorSettingsEditDeviceAddChannel ||
		route.name === routeNames.connectorSettingsEditDeviceEditChannel
	) {
		editMode.value = true;
	}
});

useMeta(() => ({
	title: t('meta.title', { connector: useEntityTitle(connectorData.value?.connector).value }),
}));
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'view-connector-detail';
</style>

<i18n>
{
  "en": {
    "meta": {
      "title": "Connector: {connector}"
    },
    "messages": {
      "notRemoved": "Connector {connector} couldn't be removed."
    },
    "buttons": {
      "edit": {
        "title": "Edit"
      }
    }
  }
}
</i18n>
