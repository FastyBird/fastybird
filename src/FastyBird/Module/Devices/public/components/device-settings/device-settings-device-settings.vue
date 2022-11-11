<template>
	<div class="fb-devices-module-device-settings-device-settings__container">
		<h3 class="fb-devices-module-device-settings-device-settings__heading">
			{{ t('headings.aboutDevice') }}
		</h3>

		<fb-ui-content
			:ph="FbSizeTypes.SMALL"
			:pv="FbSizeTypes.SMALL"
		>
			<device-settings-device-rename
				v-model="aboutField"
				:errors="{ identifier: identifierError, name: nameError, comment: commentError }"
				:device="props.deviceData.device"
			/>

			<property-settings-variable-properties-edit
				v-model="variablePropertiesFields"
				:properties="variableProperties"
			/>
		</fb-ui-content>

		<template v-if="!props.deviceData.device.draft">
			<fb-ui-divider :variant="FbUiDividerVariantTypes.GRADIENT" />

			<fb-ui-items-container>
				<template #heading>
					{{ t('headings.channels') }}
				</template>

				<template #buttons>
					<fb-ui-content :mr="FbSizeTypes.SMALL">
						<fb-ui-button
							v-if="channelsData.length > 0"
							:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
							:size="FbSizeTypes.EXTRA_SMALL"
							@click="emit('addChannel')"
						>
							<template #icon>
								<font-awesome-icon icon="plus" />
							</template>
							{{ t('buttons.addChannel.title') }}
						</fb-ui-button>
					</fb-ui-content>
				</template>

				<div
					v-if="channelsData.length === 0"
					class="fb-devices-module-device-settings-device-settings__add-item-row"
				>
					<fb-ui-button
						:variant="FbUiButtonVariantTypes.OUTLINE_DEFAULT"
						:size="FbSizeTypes.LARGE"
						block
						@click="emit('addChannel')"
					>
						<template #icon>
							<font-awesome-icon icon="plus-circle" />
						</template>
						<span>{{ t('buttons.addChannel.title') }}</span>
					</fb-ui-button>
				</div>

				<device-settings-device-channel
					v-for="channelData in channelsData"
					:key="channelData.channel.id"
					:device="props.deviceData.device"
					:channel-data="channelData"
					@edit="emit('editChannel', channelData.channel.id)"
				/>
			</fb-ui-items-container>
		</template>

		<fb-ui-divider :variant="FbUiDividerVariantTypes.GRADIENT" />

		<fb-ui-items-container>
			<template #heading>
				{{ t('headings.variableProperties') }}
			</template>

			<template #buttons>
				<fb-ui-content :mr="FbSizeTypes.SMALL">
					<fb-ui-button
						v-if="variableProperties.length > 0"
						:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
						:size="FbSizeTypes.EXTRA_SMALL"
						@click="onOpenView(ViewTypes.ADD_STATIC_PARAMETER)"
					>
						<template #icon>
							<font-awesome-icon icon="plus" />
						</template>
						{{ t('buttons.addProperty.title') }}
					</fb-ui-button>
				</fb-ui-content>
			</template>

			<div
				v-if="variableProperties.length === 0"
				class="fb-devices-module-device-settings-device-settings__add-item-row"
			>
				<fb-ui-button
					:variant="FbUiButtonVariantTypes.OUTLINE_DEFAULT"
					:size="FbSizeTypes.LARGE"
					block
					@click="onOpenView(ViewTypes.ADD_STATIC_PARAMETER)"
				>
					<template #icon>
						<font-awesome-icon icon="plus-circle" />
					</template>
					<span>{{ t('buttons.addProperty.title') }}</span>
				</fb-ui-button>
			</div>

			<property-settings-property
				v-for="property in variableProperties"
				:key="property.identifier"
				:device="props.deviceData.device"
				:property="property"
			/>
		</fb-ui-items-container>

		<fb-ui-divider :variant="FbUiDividerVariantTypes.GRADIENT" />

		<fb-ui-items-container>
			<template #heading>
				{{ t('headings.dynamicProperties') }}
			</template>

			<template #buttons>
				<fb-ui-content :mr="FbSizeTypes.SMALL">
					<fb-ui-button
						v-if="dynamicProperties.length > 0"
						:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
						:size="FbSizeTypes.EXTRA_SMALL"
						@click="onOpenView(ViewTypes.ADD_DYNAMIC_PARAMETER)"
					>
						<template #icon>
							<font-awesome-icon icon="plus" />
						</template>
						{{ t('buttons.addProperty.title') }}
					</fb-ui-button>
				</fb-ui-content>
			</template>

			<div
				v-if="dynamicProperties.length === 0"
				class="fb-devices-module-device-settings-device-settings__add-item-row"
			>
				<fb-ui-button
					:variant="FbUiButtonVariantTypes.OUTLINE_DEFAULT"
					:size="FbSizeTypes.LARGE"
					block
					@click="onOpenView(ViewTypes.ADD_DYNAMIC_PARAMETER)"
				>
					<template #icon>
						<font-awesome-icon icon="plus-circle" />
					</template>
					<span>{{ t('buttons.addProperty.title') }}</span>
				</fb-ui-button>
			</div>

			<property-settings-property
				v-for="property in dynamicProperties"
				:key="property.identifier"
				:device="props.deviceData.device"
				:property="property"
			/>
		</fb-ui-items-container>
	</div>

	<property-settings-property-add-modal
		v-if="(activeView === ViewTypes.ADD_STATIC_PARAMETER || activeView === ViewTypes.ADD_DYNAMIC_PARAMETER) && newProperty !== null"
		:property="newProperty"
		:device="props.deviceData.device"
		@close="onCloseAddProperty"
	/>

	<div
		v-if="[FbFormResultTypes.WORKING, FbFormResultTypes.OK, FbFormResultTypes.ERROR].includes(props.remoteFormResult)"
		class="fb-devices-module-device-settings-device-settings__result"
	>
		<fb-ui-loading-box
			v-if="props.remoteFormResult === FbFormResultTypes.WORKING"
			:size="FbSizeTypes.LARGE"
		>
			{{ t('messages.savingData') }}
		</fb-ui-loading-box>

		<fb-ui-result-ok v-if="props.remoteFormResult === FbFormResultTypes.OK" />
		<fb-ui-result-err v-if="props.remoteFormResult === FbFormResultTypes.ERROR" />
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useField, useForm, useFieldError } from 'vee-validate';
import * as yup from 'yup';
import { orderBy } from 'natural-orderby';
import get from 'lodash/get';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
	FbUiButton,
	FbUiContent,
	FbUiDivider,
	FbUiItemsContainer,
	FbUiLoadingBox,
	FbUiResultErr,
	FbUiResultOk,
	FbFormResultTypes,
	FbSizeTypes,
	FbUiButtonVariantTypes,
	FbUiDividerVariantTypes,
} from '@fastybird/web-ui-library';
import { DataType, ModuleSource, PropertyType } from '@fastybird/metadata-library';

import { useEntityTitle, useFlashMessage, useUuid } from '@/composables';
import { useDevices, useDeviceProperties } from '@/models';
import { IConnector, IDevice, IDeviceProperty } from '@/models/types';
import {
	DeviceSettingsDeviceChannel,
	DeviceSettingsDeviceRename,
	PropertySettingsProperty,
	PropertySettingsPropertyAddModal,
	PropertySettingsVariablePropertiesEdit,
} from '@/components';
import { IChannelData, IDeviceData } from '@/types/devices-module';

enum ViewTypes {
	NONE = 'none',
	ADD_STATIC_PARAMETER = 'addStaticParameter',
	ADD_DYNAMIC_PARAMETER = 'addDynamicParameter',
}

interface IDeviceSettingsDeviceRenameProps {
	connector: IConnector;
	deviceData: IDeviceData;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FbFormResultTypes;
	remoteFormReset?: boolean;
}

interface IDeviceSettingsDeviceSettingsForm {
	about: {
		identifier: string;
		name: string | null;
		comment: string | null;
	};
	properties: {
		static: { id: string; value: string | null }[];
	};
}

const props = withDefaults(defineProps<IDeviceSettingsDeviceRenameProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FbFormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FbFormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
	(e: 'addChannel'): void;
	(e: 'editChannel', id: string): void;
	(e: 'created', device: IDevice): void;
}>();

const { t } = useI18n();

const { generate: generateUuid } = useUuid();
const flashMessage = useFlashMessage();

const devicesStore = useDevices();
const propertiesStore = useDeviceProperties();

const activeView = ref<ViewTypes>(ViewTypes.NONE);

const variableProperties = computed<IDeviceProperty[]>((): IDeviceProperty[] => {
	return props.deviceData.properties.filter((property) => property.type.type === PropertyType.VARIABLE);
});

const dynamicProperties = computed<IDeviceProperty[]>((): IDeviceProperty[] => {
	return props.deviceData.properties.filter((property) => property.type.type === PropertyType.DYNAMIC);
});

const newPropertyId = ref<string | null>(null);
const newProperty = computed<IDeviceProperty | null>((): IDeviceProperty | null =>
	newPropertyId.value ? propertiesStore.findById(newPropertyId.value) : null
);

const { validate } = useForm<IDeviceSettingsDeviceSettingsForm>({
	validationSchema: yup.object({
		about: yup.object({
			identifier: yup.string().required(t('fields.identifier.validation.required')),
			name: yup.string().nullable().default(null),
			comment: yup.string().nullable().default(null),
		}),
		properties: yup.object({
			static: yup.array(yup.object({ id: yup.string().required(), value: yup.string().nullable().default(null) })),
		}),
	}),
	initialValues: {
		about: {
			identifier: props.deviceData.device.identifier,
			name: props.deviceData.device.name,
			comment: props.deviceData.device.comment,
		},
		properties: {
			static: variableProperties.value.map((property) => {
				return { id: property.id, value: property.value as string };
			}),
		},
	},
});

const { value: aboutField } = useField<{ identifier: string; name: string | undefined; comment: string | undefined }>('about');
const { value: variablePropertiesFields } = useField<{ id: string; value: string | undefined }[]>('properties.static');

const identifierError = useFieldError('about.identifier');
const nameError = useFieldError('about.name');
const commentError = useFieldError('about.comment');

let timer: number;

const channelsData = computed<IChannelData[]>((): IChannelData[] => {
	return orderBy<IChannelData>(
		props.deviceData.channels,
		[(v): string => v.channel.name ?? v.channel.identifier, (v): string => v.channel.identifier],
		['asc']
	);
});

const onOpenView = async (view: ViewTypes): Promise<void> => {
	if (view === ViewTypes.ADD_STATIC_PARAMETER) {
		const { id } = await propertiesStore.add({
			device: props.deviceData.device,
			type: { source: ModuleSource.MODULE_DEVICES, type: PropertyType.VARIABLE, parent: 'device' },
			draft: true,
			data: {
				identifier: generateUuid(),
				dataType: DataType.UNKNOWN,
			},
		});

		newPropertyId.value = id;
	} else if (view === ViewTypes.ADD_DYNAMIC_PARAMETER) {
		const { id } = await propertiesStore.add({
			device: props.deviceData.device,
			type: { source: ModuleSource.MODULE_DEVICES, type: PropertyType.DYNAMIC, parent: 'device' },
			draft: true,
			data: {
				identifier: generateUuid(),
				dataType: DataType.UNKNOWN,
			},
		});

		newPropertyId.value = id;
	}

	activeView.value = view;
};

const onCloseView = async (): Promise<void> => {
	if ((activeView.value === ViewTypes.ADD_STATIC_PARAMETER || activeView.value === ViewTypes.ADD_DYNAMIC_PARAMETER) && newProperty.value?.draft) {
		await propertiesStore.remove({ id: newProperty.value.id });
		newPropertyId.value = null;
	}

	activeView.value = ViewTypes.NONE;
};

const onCloseAddProperty = (saved: boolean): void => {
	if (saved) {
		activeView.value = ViewTypes.NONE;
	} else {
		onCloseView();
	}
};

const clearResult = (): void => {
	window.clearTimeout(timer);

	emit('update:remoteFormResult', FbFormResultTypes.NONE);
};

onBeforeUnmount(async (): Promise<void> => {
	if (newProperty.value?.draft) {
		await propertiesStore.remove({ id: newProperty.value.id });
		newPropertyId.value = null;
	}
});

watch(
	(): boolean => props.remoteFormSubmit,
	async (val): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			const validationResult = await validate();

			if (validationResult.valid) {
				emit('update:remoteFormResult', FbFormResultTypes.WORKING);

				if (props.deviceData.device.draft) {
					const errorMessage = t('messages.notCreated', {
						device: useEntityTitle(props.deviceData.device).value,
					});

					const variablePropertiesToCreate = variableProperties.value;
					const dynamicPropertiesToCreate = dynamicProperties.value;

					try {
						await devicesStore.edit({
							id: props.deviceData.device.id,
							data: {
								identifier: aboutField.value.identifier,
								name: aboutField.value.name,
								comment: aboutField.value.comment,
							},
						});

						await devicesStore.save({ id: props.deviceData.device.id });
					} catch (e: any) {
						if (get(e, 'exception', null) !== null) {
							flashMessage.exception(get(e, 'exception', null), t('messages.notCreated'));
						} else {
							flashMessage.error(t('messages.notCreated'));
						}

						emit('update:remoteFormResult', FbFormResultTypes.ERROR);

						timer = window.setTimeout(clearResult, 2000);

						return;
					}

					let success = true;

					for (const variableProperty of variablePropertiesToCreate) {
						try {
							await propertiesStore.save({ id: variableProperty.id });
						} catch (e: any) {
							if (get(e, 'exception', null) !== null) {
								flashMessage.exception(get(e, 'exception', null), errorMessage);
							} else {
								flashMessage.error(errorMessage);
							}

							emit('update:remoteFormResult', FbFormResultTypes.ERROR);

							success = false;

							break;
						}
					}

					for (const dynamicProperty of dynamicPropertiesToCreate) {
						try {
							await propertiesStore.save({ id: dynamicProperty.id });
						} catch (e: any) {
							if (get(e, 'exception', null) !== null) {
								flashMessage.exception(get(e, 'exception', null), errorMessage);
							} else {
								flashMessage.error(errorMessage);
							}

							emit('update:remoteFormResult', FbFormResultTypes.ERROR);

							success = false;

							break;
						}
					}

					for (const variablePropertyField of variablePropertiesFields.value) {
						try {
							await propertiesStore.edit({
								id: variablePropertyField.id,
								data: {
									value: variablePropertyField.value,
								},
							});
						} catch (e: any) {
							if (get(e, 'exception', null) !== null) {
								flashMessage.exception(get(e, 'exception', null), errorMessage);
							} else {
								flashMessage.error(errorMessage);
							}

							emit('update:remoteFormResult', FbFormResultTypes.ERROR);

							timer = window.setTimeout(clearResult, 2000);

							success = false;

							break;
						}
					}

					if (success) {
						emit('update:remoteFormResult', FbFormResultTypes.OK);

						timer = window.setTimeout((): void => {
							clearResult();

							emit('created', props.deviceData.device);
						}, 2000);
					}
				} else {
					const errorMessage = t('messages.notEdited', {
						device: useEntityTitle(props.deviceData.device).value,
					});

					emit('update:remoteFormResult', FbFormResultTypes.WORKING);

					try {
						await devicesStore.edit({
							id: props.deviceData.device.id,
							data: {
								name: aboutField.value.name,
								comment: aboutField.value.comment,
							},
						});
					} catch (e: any) {
						if (get(e, 'exception', null) !== null) {
							flashMessage.exception(get(e, 'exception', null), errorMessage);
						} else {
							flashMessage.error(errorMessage);
						}

						emit('update:remoteFormResult', FbFormResultTypes.ERROR);

						timer = window.setTimeout(clearResult, 2000);

						return;
					}

					let success = true;

					for (const variablePropertyField of variablePropertiesFields.value) {
						try {
							await propertiesStore.edit({
								id: variablePropertyField.id,
								data: {
									value: variablePropertyField.value,
								},
							});
						} catch (e: any) {
							if (get(e, 'exception', null) !== null) {
								flashMessage.exception(get(e, 'exception', null), errorMessage);
							} else {
								flashMessage.error(errorMessage);
							}

							emit('update:remoteFormResult', FbFormResultTypes.ERROR);

							timer = window.setTimeout(clearResult, 2000);

							success = false;

							break;
						}
					}

					if (success) {
						emit('update:remoteFormResult', FbFormResultTypes.OK);

						timer = window.setTimeout(clearResult, 2000);
					}
				}
			}
		}
	}
);
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'device-settings-device-settings';
</style>

<i18n>
{
  "en": {
    "headings": {
      "aboutDevice": "About device",
      "variableProperties": "Device config parameters",
      "dynamicProperties": "Device data parameters",
      "channels": "Channels"
    },
    "buttons": {
      "addChannel": {
        "title": "Add channel"
      },
      "addProperty": {
        "title": "Add parameter"
      }
    },
    "messages": {
      "savingData": "Saving device",
      "notCreated": "New device couldn't be created.",
      "notEdited": "Device {device} couldn't be edited."
    },
    "fields": {
      "identifier": {
        "validation": {
          "required": "Please fill in device identifier"
        }
      }
    }
  }
}
</i18n>
