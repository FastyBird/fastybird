<template>
	<div
		v-loading="isLoading || channelData === null"
		:element-loading-text="t('texts.misc.loadingDevice')"
		class="flex flex-col overflow-hidden h-full"
	>
		<template v-if="channelData !== null">
			<fb-app-bar-heading
				v-if="isXSDevice"
				teleport
			>
				<template #icon>
					<devices-device-icon :device="device" />
				</template>

				<template #title>
					{{ channelData.channel.draft ? t('headings.add') : t('headings.edit') }}
				</template>

				<template #subtitle>
					{{ useEntityTitle(device).value }}
				</template>
			</fb-app-bar-heading>

			<fb-app-bar-button
				v-if="isXSDevice"
				teleport
				:align="AppBarButtonAlignTypes.LEFT"
				small
				@click="onClose"
			>
				<span class="uppercase">{{ t('buttons.close.title') }}</span>
			</fb-app-bar-button>

			<fb-app-bar-button
				v-if="isXSDevice"
				teleport
				:align="AppBarButtonAlignTypes.LEFT"
				small
				@click="onSubmit"
			>
				<span class="uppercase">{{ t('buttons.save.title') }}</span>
			</fb-app-bar-button>

			<el-scrollbar class="flex-1">
				<channel-settings-channel-settings
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-result="remoteFormResult"
					:loading="isLoading"
					:device="device"
					:channel-data="channelData"
					@created="onCreated"
					@add-static-property="onAddStaticProperty"
					@add-dynamic-property="onAddDynamicProperty"
					@edit-property="onEditProperty"
					@remove-property="onRemoveProperty"
				/>
			</el-scrollbar>

			<div
				v-if="!isXSDevice"
				class="flex flex-row gap-2 justify-end b-t b-t-solid p-2 shadow-top z-10"
			>
				<el-button
					:loading="remoteFormResult === FormResultTypes.WORKING"
					:disabled="remoteFormResult !== FormResultTypes.NONE"
					:icon="remoteFormResult === FormResultTypes.OK ? FarCircleCheck : remoteFormResult === FormResultTypes.ERROR ? FarCircleXmark : undefined"
					type="primary"
					class="order-2"
					@click="onSubmit"
				>
					{{ t('buttons.save.title') }}
				</el-button>

				<el-button
					:disabled="remoteFormResult !== FormResultTypes.NONE"
					class="order-1"
					@click="onClose"
				>
					{{ t('buttons.close.title') }}
				</el-button>
			</div>

			<property-settings-property-add-modal
				v-if="newProperty !== null"
				:property="newProperty"
				:channel="channelData.channel"
				:device="device"
				@created="onPropertyCreated"
				@close="onCloseAddProperty"
			/>

			<property-settings-property-edit-modal
				v-if="editProperty !== null"
				:property="editProperty"
				:channel="channelData.channel"
				:device="device"
				@close="onCloseEditProperty"
			/>
		</template>
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, onBeforeUnmount, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRoute, useRouter } from 'vue-router';
import { orderBy } from 'natural-orderby';
import get from 'lodash/get';
import { ElButton, ElMessageBox, ElScrollbar, vLoading } from 'element-plus';

import { FarCircleCheck, FarCircleXmark } from '@fastybird/web-ui-icons';
import { AppBarButtonAlignTypes, FbAppBarButton, FbAppBarHeading } from '@fastybird/web-ui-library';
import { DataType, ModuleSource, PropertyType } from '@fastybird/metadata-library';

import { useBreakpoints, useEntityTitle, useFlashMessage, useRoutesNames, useUuid } from '../composables';
import { ApplicationError } from '../errors';
import { useChannelControls, useChannelProperties, useChannels, useDevices } from '../models';
import { IChannel, IChannelControl, IChannelProperty, IDevice } from '../models/types';
import {
	ChannelSettingsChannelSettings,
	DevicesDeviceIcon,
	PropertySettingsPropertyAddModal,
	PropertySettingsPropertyEditModal,
} from '../components';
import { FormResultTypes, IChannelData } from '../types';
import { IViewChanelSettingsProps } from './view-channel-settings.types';

defineOptions({
	name: 'ViewChannelSettings',
});

const props = withDefaults(defineProps<IViewChanelSettingsProps>(), {
	connectorId: null,
});

const { t } = useI18n();
const router = useRouter();
const route = useRoute();

const flashMessage = useFlashMessage();
const { generate: generateUuid, validate: validateUuid } = useUuid();
const { isXSDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const { meta } = useMeta({});

const devicesStore = useDevices();
const channelsStore = useChannels();
const channelControlsStore = useChannelControls();
const channelPropertiesStore = useChannelProperties();
const propertiesStore = useChannelProperties();

if (props.id !== null && !validateUuid(props.id)) {
	throw new Error('Channel identifier is not valid');
}

if (!validateUuid(props.deviceId)) {
	throw new Error('Device identifier is not valid');
}

const id = ref<string>(props.id ?? generateUuid());

const isLoading = computed<boolean>((): boolean => {
	if (channelsStore.getting(id.value)) {
		return true;
	}

	if (channelsStore.findById(id.value)) {
		return false;
	}

	return channelsStore.fetching(props.deviceId);
});

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FormResultTypes>(FormResultTypes.NONE);

const newPropertyId = ref<string | null>(null);
const newProperty = computed<IChannelProperty | null>((): IChannelProperty | null =>
	newPropertyId.value ? propertiesStore.findById(newPropertyId.value) : null
);

const editPropertyId = ref<string | null>(null);
const editProperty = computed<IChannelProperty | null>((): IChannelProperty | null =>
	editPropertyId.value ? propertiesStore.findById(editPropertyId.value) : null
);

const isConnectorSettingsRoute = computed<boolean>((): boolean => {
	return (
		route.matched.find((matched) => {
			return matched.name === routeNames.connectorSettingsEditDeviceAddChannel || matched.name === routeNames.connectorSettingsEditDeviceEditChannel;
		}) !== undefined
	);
});

const device = computed<IDevice>((): IDevice => {
	const device = devicesStore.findById(props.deviceId);

	if (device === null) {
		throw new Error('Device was not found');
	}

	return device;
});

if (props.id === null) {
	await channelsStore.add({
		id: id.value,
		device: device.value,
		type: { source: ModuleSource.MODULE_DEVICES, type: 'generic' },
		draft: true,
		data: {
			identifier: generateUuid().toString(),
		},
	});
}

const channelData = computed<IChannelData | null>((): IChannelData | null => {
	const channel = channelsStore.findById(id.value);

	if (channel === null) {
		return null;
	}

	return {
		channel: channel,
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
});

const onSubmit = (): void => {
	remoteFormSubmit.value = true;
};

const onClose = (): void => {
	if (isConnectorSettingsRoute.value) {
		router.push({ name: routeNames.connectorSettingsEditDevice, params: { id: props.connectorId, deviceId: props.deviceId } });
	} else {
		router.push({ name: routeNames.deviceSettings, params: { id: props.deviceId } });
	}
};

const onCreated = (channel: IChannel): void => {
	if (isConnectorSettingsRoute.value) {
		router.push({
			name: routeNames.connectorSettingsEditDeviceEditChannel,
			params: { id: props.connectorId, deviceId: props.deviceId, channelId: channel.id },
		});
	} else {
		router.push({ name: routeNames.deviceSettingsEditChannel, params: { id: props.deviceId, channelId: channel.id } });
	}
};

const onAddStaticProperty = async (): Promise<void> => {
	if (channelData.value === null) {
		return;
	}

	const { id } = await propertiesStore.add({
		channel: channelData.value.channel,
		type: { source: ModuleSource.MODULE_DEVICES, type: PropertyType.VARIABLE, parent: 'channel' },
		draft: true,
		data: {
			identifier: generateUuid(),
			dataType: DataType.UNKNOWN,
		},
	});

	newPropertyId.value = id;
};

const onAddDynamicProperty = async (): Promise<void> => {
	if (channelData.value === null) {
		return;
	}

	const { id } = await propertiesStore.add({
		channel: channelData.value.channel,
		type: { source: ModuleSource.MODULE_DEVICES, type: PropertyType.DYNAMIC, parent: 'channel' },
		draft: true,
		data: {
			identifier: generateUuid(),
			dataType: DataType.UNKNOWN,
		},
	});

	newPropertyId.value = id;
};

const onPropertyCreated = async (): Promise<void> => {
	newPropertyId.value = null;
};

const onCloseAddProperty = async (canceled: boolean): Promise<void> => {
	if (canceled && newProperty.value?.draft) {
		await propertiesStore.remove({ id: newProperty.value.id });
	}

	newPropertyId.value = null;
};

const onEditProperty = async (id: string): Promise<void> => {
	const property = propertiesStore.findById(id);

	if (property === null) {
		return;
	}

	editPropertyId.value = id;
};

const onCloseEditProperty = async (): Promise<void> => {
	editPropertyId.value = null;
};

const onRemoveProperty = async (id: string): Promise<void> => {
	const property = propertiesStore.findById(id);

	if (property === null) {
		return;
	}

	ElMessageBox.confirm(
		t('messages.properties.confirmRemoveChannelProperty', { property: useEntityTitle(property).value }),
		t('headings.properties.remove'),
		{
			confirmButtonText: t('buttons.yes.title'),
			cancelButtonText: t('buttons.no.title'),
			type: 'warning',
		}
	)
		.then(async (): Promise<void> => {
			const errorMessage = t('messages.properties.notRemoved', {
				property: useEntityTitle(property).value,
			});

			propertiesStore.remove({ id: property.id }).catch((e): void => {
				if (get(e, 'exception', null) !== null) {
					flashMessage.exception(get(e, 'exception', null), errorMessage);
				} else {
					flashMessage.error(errorMessage);
				}
			});
		})
		.catch(() => {
			flashMessage.info(
				t('messages.properties.removeCanceled', {
					property: useEntityTitle(property).value,
				})
			);
		});
};

onBeforeMount(async (): Promise<void> => {
	fetchChannel(id.value).catch((e) => {
		if (get(e, 'exception.response.status', 0) === 404) {
			throw new ApplicationError('Channel Not Found', e, { statusCode: 404, message: 'Channel Not Found' });
		} else {
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		}
	});

	if (!isLoading.value && channelsStore.findById(id.value) === null) {
		throw new ApplicationError('Channel Not Found', null, { statusCode: 404, message: 'Channel Not Found' });
	}
});

onBeforeUnmount(async (): Promise<void> => {
	if (newProperty.value?.draft) {
		await propertiesStore.remove({ id: newProperty.value.id });
		newPropertyId.value = null;
	}
});

onUnmounted((): void => {
	if (channelData.value?.channel.draft) {
		channelsStore.remove({ id: channelData.value?.channel.id });
	}
});

const fetchChannel = async (id: string): Promise<void> => {
	if (!isLoading.value && !channelsStore.firstLoadFinished(props.deviceId)) {
		await channelsStore.get({ id });
	}
};

watch(
	(): boolean => isLoading.value,
	(val: boolean): void => {
		if (!val && channelData.value === null) {
			throw new ApplicationError('Channel Not Found', null, { statusCode: 404, message: 'Channel Not Found' });
		}
	}
);

watch(
	(): IChannelData | null => channelData.value,
	(val: IChannelData | null): void => {
		if (val !== null) {
			meta.title = t('meta.channels.settings.title', { channel: useEntityTitle(val.channel).value });
		}

		if (!isLoading.value && val === null) {
			throw new ApplicationError('Channel Not Found', null, { statusCode: 404, message: 'Channel Not Found' });
		}
	}
);
</script>
