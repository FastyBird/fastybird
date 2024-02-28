<template>
	<fb-ui-modal-form
		:transparent-bg="true"
		:lock-submit-button="remoteFormResult !== FbFormResultTypes.NONE"
		:state="remoteFormResult"
		:submit-btn-label="isDraft ? t('buttons.add.title') : t('buttons.save.title')"
		:layout="isExtraSmallDevice ? FbUiModalLayoutTypes.PHONE : isSmallDevice ? FbUiModalLayoutTypes.TABLET : FbUiModalLayoutTypes.DEFAULT"
		@submit="onSubmitForm"
		@cancel="onClose"
		@close="onClose"
	>
		<template #title>
			{{ t('headings.add') }}
		</template>

		<template #icon>
			<font-awesome-icon icon="plus" />
		</template>

		<template #form>
			<template v-if="isConnectorProperty || props.property.type.type === PropertyType.VARIABLE">
				<property-settings-property-form
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-result="remoteFormResult"
					:connector="props.connector"
					:device="props.device"
					:channel="props.channel"
					:property="props.property"
					@added="onAdded"
				/>
			</template>

			<template v-else>
				<template v-if="activeView === PropertySettingsPropertyAddModalViewTypes.SELECT_TYPE">
					<div class="fb-devices-module-property-settings-property-add-modal__row">
						<div class="fb-devices-module-property-settings-property-add-modal__row-item">
							<fb-ui-button
								:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
								:size="FbSizeTypes.LARGE"
								block
								class="fb-devices-module-property-settings-property-add-modal__button"
								@click.prevent="onOpenView(PropertySettingsPropertyAddModalViewTypes.NEW_PROPERTY)"
							>
								<font-awesome-icon
									icon="file"
									size="2x"
									class="fb-devices-module-property-settings-property-add-modal__button-icon"
								/>

								{{ t('buttons.addTypeNew.title') }}
							</fb-ui-button>
						</div>

						<div class="fb-devices-module-property-settings-property-add-modal__row-item">
							<fb-ui-button
								:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
								:size="FbSizeTypes.LARGE"
								block
								class="fb-devices-module-property-settings-property-add-modal__button"
								@click.prevent="onOpenView(PropertySettingsPropertyAddModalViewTypes.SELECT_CONNECTOR)"
							>
								<font-awesome-icon
									icon="clone"
									size="2x"
									class="fb-devices-module-property-settings-property-add-modal__button-icon"
								/>

								{{ t('buttons.addTypeCloned.title') }}
							</fb-ui-button>
						</div>
					</div>

					<fb-ui-alert :variant="FbUiVariantTypes.INFO">
						<h3>New parameter</h3>
						<p>This option will create new independent item parameter to receive or set data. This type of parameter could be fully customized.</p>

						<hr />

						<h3>Mapped parameter</h3>
						<p>
							This option will create parameter mapped to existing parameter. This type of parameter could not be configured, every settings is used
							from mapped parent one.
						</p>
					</fb-ui-alert>
				</template>

				<property-settings-property-form
					v-if="
						activeView === PropertySettingsPropertyAddModalViewTypes.NEW_PROPERTY ||
						activeView === PropertySettingsPropertyAddModalViewTypes.MAPPED_PROPERTY
					"
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-result="remoteFormResult"
					:connector="props.connector"
					:device="props.device"
					:channel="props.channel"
					:property="props.property"
					@added="onAdded"
				/>

				<template v-if="activeView === PropertySettingsPropertyAddModalViewTypes.SELECT_CONNECTOR">
					<fb-ui-items-container>
						<template #heading>
							{{ t('headings.connectorSelect') }}
						</template>

						<fb-ui-content :pv="FbSizeTypes.SMALL">
							<fb-ui-item
								v-for="connectorItem in connectors"
								:key="connectorItem.id"
								:disabled="connectorItem.disabled"
								:variant="FbUiItemVariantTypes.DEFAULT"
								class="fb-devices-module-property-settings-property-add-modal__item"
								@click="onSelectConnector(connectorItem)"
							>
								<template #icon>
									<connectors-connector-icon :connector="connectorItem" />
								</template>

								<template #heading>
									{{ useEntityTitle(connectorItem).value }}
								</template>

								<template
									v-if="connectorItem.hasComment"
									#subheading
								>
									{{ connectorItem.comment }}
								</template>

								<template #button>
									<font-awesome-icon icon="chevron-right" />
								</template>
							</fb-ui-item>
						</fb-ui-content>

						<fb-ui-no-results
							v-if="!connectors.length"
							:size="FbSizeTypes.LARGE"
							:variant="FbUiVariantTypes.PRIMARY"
							class="fb-devices-module-property-settings-property-add-modal__no-results"
						>
							<template #icon>
								<font-awesome-icon icon="ethernet" />
							</template>

							<template #second-icon>
								<font-awesome-icon icon="exclamation" />
							</template>

							{{ t('texts.noConnectors') }}
						</fb-ui-no-results>
					</fb-ui-items-container>
				</template>

				<template v-if="activeView === PropertySettingsPropertyAddModalViewTypes.SELECT_DEVICE">
					<fb-ui-items-container>
						<template #heading>
							{{ t('headings.deviceSelect') }}
						</template>

						<fb-ui-content :pv="FbSizeTypes.SMALL">
							<fb-ui-item
								v-for="deviceItem in devices"
								:key="deviceItem.id"
								:disabled="deviceItem.disabled"
								:variant="FbUiItemVariantTypes.DEFAULT"
								class="fb-devices-module-property-settings-property-add-modal__item"
								@click="onSelectDevice(deviceItem)"
							>
								<template #icon>
									<devices-device-icon :device="deviceItem" />
								</template>

								<template #heading>
									{{ useEntityTitle(deviceItem).value }}
								</template>

								<template
									v-if="deviceItem.hasComment"
									#subheading
								>
									{{ deviceItem.comment }}
								</template>

								<template #button>
									<font-awesome-icon icon="chevron-right" />
								</template>
							</fb-ui-item>
						</fb-ui-content>

						<fb-ui-no-results
							v-if="!devices.length"
							:size="FbSizeTypes.LARGE"
							:variant="FbUiVariantTypes.PRIMARY"
							class="fb-devices-module-property-settings-property-add-modal__no-results"
						>
							<template #icon>
								<font-awesome-icon icon="plug" />
							</template>

							<template #second-icon>
								<font-awesome-icon icon="exclamation" />
							</template>

							{{ t('texts.noDevices') }}
						</fb-ui-no-results>
					</fb-ui-items-container>
				</template>

				<template v-if="activeView === PropertySettingsPropertyAddModalViewTypes.SELECT_CHANNEL">
					<fb-ui-items-container>
						<template #heading>
							{{ t('headings.channelSelect') }}
						</template>

						<fb-ui-content :pv="FbSizeTypes.SMALL">
							<fb-ui-item
								v-for="channelItem in channels"
								:key="channelItem.id"
								:disabled="channelItem.disabled"
								:variant="FbUiItemVariantTypes.DEFAULT"
								class="fb-devices-module-property-settings-property-add-modal__item"
								@click="onSelectChannel(channelItem)"
							>
								<template #icon>
									<font-awesome-icon icon="cube" />
								</template>

								<template #heading>
									{{ useEntityTitle(channelItem).value }}
								</template>

								<template
									v-if="channelItem.hasComment"
									#subheading
								>
									{{ channelItem.comment }}
								</template>

								<template #button>
									<font-awesome-icon icon="chevron-right" />
								</template>
							</fb-ui-item>
						</fb-ui-content>

						<fb-ui-no-results
							v-if="!channels.length"
							:size="FbSizeTypes.LARGE"
							:variant="FbUiVariantTypes.PRIMARY"
							class="fb-devices-module-property-settings-property-add-modal__no-results"
						>
							<template #icon>
								<font-awesome-icon icon="cube" />
							</template>

							<template #second-icon>
								<font-awesome-icon icon="exclamation" />
							</template>

							{{ t('texts.noChannels') }}
						</fb-ui-no-results>
					</fb-ui-items-container>
				</template>

				<template v-if="activeView === PropertySettingsPropertyAddModalViewTypes.SELECT_PARENT">
					<fb-ui-items-container>
						<template #heading>
							{{ t('headings.parentSelect') }}
						</template>

						<fb-ui-content :pv="FbSizeTypes.SMALL">
							<fb-ui-item
								v-for="propertyItem in properties"
								:key="propertyItem.id"
								:variant="FbUiItemVariantTypes.DEFAULT"
								@click="onSelectParent(propertyItem)"
							>
								<template #icon>
									<properties-property-icon :property="propertyItem" />
								</template>

								<template #heading>
									{{ useEntityTitle(propertyItem).value }}
								</template>

								<template #button>
									<font-awesome-icon icon="chevron-right" />
								</template>
							</fb-ui-item>
						</fb-ui-content>

						<fb-ui-no-results
							v-if="!properties.length"
							:size="FbSizeTypes.LARGE"
							:variant="FbUiVariantTypes.PRIMARY"
							class="fb-devices-module-property-settings-property-add-modal__no-results"
						>
							<template #icon>
								<font-awesome-icon icon="cube" />
							</template>

							<template #second-icon>
								<font-awesome-icon icon="exclamation" />
							</template>

							<template v-if="isDeviceProperty">
								{{ t('texts.noDeviceProperties') }}
							</template>

							<template v-if="isChannelProperty">
								{{ t('texts.noChannelProperties') }}
							</template>
						</fb-ui-no-results>
					</fb-ui-items-container>
				</template>
			</template>
		</template>

		<template
			v-if="!isConnectorProperty && props.property.type.type !== PropertyType.VARIABLE"
			#footer
		>
			<template v-if="activeView === PropertySettingsPropertyAddModalViewTypes.SELECT_TYPE">
				<fb-ui-button
					:size="FbSizeTypes.LARGE"
					:variant="FbUiButtonVariantTypes.LINK_DEFAULT"
					uppercase
					name="close"
					@click="onClose"
				>
					{{ t('buttons.close.title') }}
				</fb-ui-button>
			</template>

			<template
				v-if="
					activeView === PropertySettingsPropertyAddModalViewTypes.NEW_PROPERTY ||
					activeView === PropertySettingsPropertyAddModalViewTypes.MAPPED_PROPERTY
				"
			>
				<fb-ui-button
					:size="FbSizeTypes.LARGE"
					:variant="FbUiButtonVariantTypes.LINK_DEFAULT"
					uppercase
					name="cancel"
					@click="onClose"
				>
					{{ t('buttons.cancel.title') }}
				</fb-ui-button>

				<fb-ui-button
					:size="FbSizeTypes.LARGE"
					:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
					:loading="remoteFormResult === FbFormResultTypes.WORKING"
					uppercase
					name="submit"
					@click="onSubmitForm"
				>
					{{ isDraft ? t('buttons.add.title') : t('buttons.save.title') }}
				</fb-ui-button>
			</template>

			<template
				v-if="
					activeView === PropertySettingsPropertyAddModalViewTypes.SELECT_CONNECTOR ||
					activeView === PropertySettingsPropertyAddModalViewTypes.SELECT_DEVICE ||
					activeView === PropertySettingsPropertyAddModalViewTypes.SELECT_CHANNEL ||
					activeView === PropertySettingsPropertyAddModalViewTypes.SELECT_PARENT
				"
			>
				<fb-ui-button
					:size="FbSizeTypes.LARGE"
					:variant="FbUiButtonVariantTypes.LINK_DEFAULT"
					uppercase
					name="close"
					@click="onClose"
				>
					{{ t('buttons.close.title') }}
				</fb-ui-button>

				<fb-ui-button
					:size="FbSizeTypes.LARGE"
					:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
					:loading="remoteFormResult === FbFormResultTypes.WORKING"
					uppercase
					name="back"
					@click="onBack"
				>
					{{ t('buttons.back.title') }}
				</fb-ui-button>
			</template>
		</template>
	</fb-ui-modal-form>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { orderBy } from 'natural-orderby';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
	FbUiAlert,
	FbUiButton,
	FbUiContent,
	FbUiItem,
	FbUiItemsContainer,
	FbUiModalForm,
	FbUiNoResults,
	FbFormResultTypes,
	FbSizeTypes,
	FbUiButtonVariantTypes,
	FbUiItemVariantTypes,
	FbUiModalLayoutTypes,
	FbUiVariantTypes,
} from '@fastybird/web-ui-library';
import { PropertyType } from '@fastybird/metadata-library';

import { useBreakpoints, useEntityTitle } from '../../composables';
import { useChannelProperties, useChannels, useConnectorProperties, useConnectors, useDeviceProperties, useDevices } from '../../models';
import { IChannel, IChannelProperty, IConnector, IConnectorProperty, IDevice, IDeviceProperty } from '../../models/types';
import { ConnectorsConnectorIcon, DevicesDeviceIcon, PropertiesPropertyIcon, PropertySettingsPropertyForm } from '../../components';
import {
	IChannelListItem,
	IConnectorListItem,
	IDeviceListItem,
	IPropertySettingsPropertyAddModalProps,
	PropertySettingsPropertyAddModalViewTypes,
} from './property-settings-property-add-modal.types';

const props = defineProps<IPropertySettingsPropertyAddModalProps>();

const emit = defineEmits<{
	(e: 'close', saved: boolean): void;
}>();

const { t } = useI18n();
const { isExtraSmallDevice, isSmallDevice } = useBreakpoints();

const connectorsStore = useConnectors();
const connectorPropertiesStore = useConnectorProperties();
const devicesStore = useDevices();
const devicePropertiesStore = useDeviceProperties();
const channelsStore = useChannels();
const channelPropertiesStore = useChannelProperties();

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FbFormResultTypes>(FbFormResultTypes.NONE);

const activeView = ref<PropertySettingsPropertyAddModalViewTypes>(PropertySettingsPropertyAddModalViewTypes.SELECT_TYPE);

const isDraft = computed<boolean>((): boolean => {
	if (isChannelProperty.value) {
		return props.channel ? props.channel.draft : false;
	}

	if (isDeviceProperty.value) {
		return props.device ? props.device.draft : false;
	}

	if (isConnectorProperty.value) {
		return props.connector ? props.connector.draft : false;
	}

	return false;
});

const isConnectorProperty = computed<boolean>((): boolean => props.connector !== undefined);
const isDeviceProperty = computed<boolean>((): boolean => props.device !== undefined && props.channel === undefined);
const isChannelProperty = computed<boolean>((): boolean => props.device !== undefined && props.channel !== undefined);

const selectedConnector = ref<IConnector | null>(null);
const selectedDevice = ref<IDevice | null>(null);
const selectedChannel = ref<IChannel | null>(null);

const connectors = computed<IConnectorListItem[]>((): IConnectorListItem[] => {
	return orderBy<IConnectorListItem>(
		Object.values(connectorsStore.data)
			.filter((connector) => !connector.draft)
			.map((connector) => {
				if (!isConnectorProperty.value) {
					return { ...connector, ...{ disabled: false } };
				}

				return { ...connector, ...{ disabled: !('connector' in props.property && connector.id !== props.property.connector.id) } };
			}),
		[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
		['asc']
	);
});

const devices = computed<IDeviceListItem[]>((): IDeviceListItem[] => {
	if (selectedConnector.value === null) {
		return [];
	}

	return orderBy<IDeviceListItem>(
		Object.values(devicesStore.findForConnector(selectedConnector.value.id))
			.filter((device) => !device.draft)
			.map((device) => {
				if (!isDeviceProperty.value) {
					return { ...device, ...{ disabled: false } };
				}

				return { ...device, ...{ disabled: !('device' in props.property && device.id !== props.property.device.id) } };
			}),
		[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
		['asc']
	);
});

const channels = computed<IChannelListItem[]>((): IChannelListItem[] => {
	if (selectedDevice.value === null) {
		return [];
	}

	return orderBy<IChannelListItem>(
		Object.values(channelsStore.findForDevice(selectedDevice.value.id))
			.filter((channel) => !channel.draft)
			.map((channel) => {
				if (!isChannelProperty.value) {
					return { ...channel, ...{ disabled: false } };
				}

				return { ...channel, ...{ disabled: !('channel' in props.property && channel.id !== props.property.channel.id) } };
			}),
		[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
		['asc']
	);
});

const properties = computed<(IChannelProperty | IConnectorProperty | IDeviceProperty)[]>(
	(): (IChannelProperty | IConnectorProperty | IDeviceProperty)[] => {
		if (isChannelProperty.value) {
			if (selectedChannel.value === null) {
				return [];
			}

			return orderBy<IChannelProperty>(
				Object.values(channelPropertiesStore.findForChannel(selectedChannel.value.id)).filter(
					(property) => !property.draft && property.type.type === props.property.type.type && property.parent === null
				),
				[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
				['asc']
			);
		}

		if (isDeviceProperty.value) {
			if (selectedDevice.value === null) {
				return [];
			}

			return orderBy<IDeviceProperty>(
				Object.values(devicePropertiesStore.findForDevice(selectedDevice.value.id))
					.filter((property) => !property.draft)
					.filter((property) => property.type.type === props.property.type.type),
				[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
				['asc']
			);
		}

		if (isConnectorProperty.value) {
			if (selectedConnector.value === null) {
				return [];
			}

			return orderBy<IConnectorProperty>(
				Object.values(connectorPropertiesStore.findForConnector(selectedConnector.value.id))
					.filter((property) => !property.draft)
					.filter((property) => property.type.type === props.property.type.type),
				[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
				['asc']
			);
		}

		return [];
	}
);

const onSelectConnector = (connector: IConnectorListItem): void => {
	if (connector.disabled) {
		return;
	}

	selectedConnector.value = connector;

	activeView.value = isConnectorProperty.value
		? PropertySettingsPropertyAddModalViewTypes.SELECT_PARENT
		: PropertySettingsPropertyAddModalViewTypes.SELECT_DEVICE;
};

const onSelectDevice = (device: IDeviceListItem): void => {
	if (device.disabled) {
		return;
	}

	selectedDevice.value = device;

	activeView.value = isDeviceProperty.value
		? PropertySettingsPropertyAddModalViewTypes.SELECT_PARENT
		: PropertySettingsPropertyAddModalViewTypes.SELECT_CHANNEL;
};

const onSelectChannel = (channel: IChannelListItem): void => {
	if (channel.disabled) {
		return;
	}

	selectedChannel.value = channel;

	activeView.value = PropertySettingsPropertyAddModalViewTypes.SELECT_PARENT;
};

const onSelectParent = (property: IChannelProperty | IConnectorProperty | IDeviceProperty): void => {
	if (isDeviceProperty.value) {
		devicePropertiesStore.edit({
			id: props.property.id,
			parent: property as IDeviceProperty,
			data: {},
		});

		activeView.value = PropertySettingsPropertyAddModalViewTypes.MAPPED_PROPERTY;
	} else if (isChannelProperty.value) {
		channelPropertiesStore.edit({
			id: props.property.id,
			parent: property as IChannelProperty,
			data: {},
		});

		activeView.value = PropertySettingsPropertyAddModalViewTypes.MAPPED_PROPERTY;
	}
};

const onSubmitForm = (): void => {
	remoteFormSubmit.value = true;
};

const onClose = (): void => {
	emit('close', false);
};

const onBack = (): void => {
	if (activeView.value === PropertySettingsPropertyAddModalViewTypes.SELECT_CONNECTOR) {
		activeView.value = PropertySettingsPropertyAddModalViewTypes.SELECT_TYPE;
	} else if (activeView.value === PropertySettingsPropertyAddModalViewTypes.SELECT_DEVICE) {
		activeView.value = PropertySettingsPropertyAddModalViewTypes.SELECT_CONNECTOR;
	} else if (activeView.value === PropertySettingsPropertyAddModalViewTypes.SELECT_CHANNEL) {
		activeView.value = PropertySettingsPropertyAddModalViewTypes.SELECT_DEVICE;
	} else if (activeView.value === PropertySettingsPropertyAddModalViewTypes.SELECT_PARENT) {
		if (isChannelProperty.value) {
			activeView.value = PropertySettingsPropertyAddModalViewTypes.SELECT_CHANNEL;
		} else if (isDeviceProperty.value) {
			activeView.value = PropertySettingsPropertyAddModalViewTypes.SELECT_DEVICE;
		} else if (isConnectorProperty.value) {
			activeView.value = PropertySettingsPropertyAddModalViewTypes.SELECT_CONNECTOR;
		}
	}
};

const onOpenView = (view: PropertySettingsPropertyAddModalViewTypes): void => {
	activeView.value = view;
};

const onAdded = (): void => {
	emit('close', true);
};

watch(
	(): FbFormResultTypes => remoteFormResult.value,
	(actual, previous): void => {
		if (actual === FbFormResultTypes.NONE && previous === FbFormResultTypes.OK) {
			emit('close', true);
		}
	}
);
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'property-settings-property-add-modal';
</style>

<i18n>
{
  "en": {
    "headings": {
      "add": "Add parameter",
      "connectorSelect": "Select connector",
      "deviceSelect": "Select device",
      "channelSelect": "Select device channel",
      "parentSelect": "Select parameter"
    },
    "buttons": {
      "addTypeNew": {
        "title": "Create new"
      },
      "addTypeCloned": {
        "title": "Map existing"
      },
      "cancel": {
        "title": "Cancel"
      },
      "close": {
        "title": "Close"
      },
      "add": {
        "title": "Add"
      },
      "save": {
        "title": "Save"
      },
      "back": {
        "title": "Back"
      }
    },
    "texts": {
      "noConnectors": "No connectors registered",
      "noDevices": "No devices connected under selected connector",
      "noChannels": "No channels under selected device",
      "noDeviceProperties": "No parameters under selected device",
      "noChannelProperties": "No parameters under selected channel"
    }
  }
}
</i18n>
