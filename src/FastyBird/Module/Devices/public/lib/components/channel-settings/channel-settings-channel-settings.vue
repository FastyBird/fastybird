<template>
	<div class="fb-devices-module-channel-settings-channel-settings__container">
		<h3 class="fb-devices-module-channel-settings-channel-settings__heading">
			{{ t('headings.aboutChannel') }}
		</h3>

		<fb-ui-content
			:ph="FbSizeTypes.SMALL"
			:pv="FbSizeTypes.SMALL"
		>
			<channel-settings-channel-rename
				v-model="aboutField"
				:errors="{ identifier: identifierError, name: nameError, comment: commentError }"
				:channel="props.channelData.channel"
			/>

			<property-settings-variable-properties-edit
				v-model="variablePropertiesFields"
				:properties="variableProperties"
			/>
		</fb-ui-content>

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
				class="fb-devices-module-channel-settings-channel-settings__add-item-row"
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
				:device="props.device"
				:channel="props.channelData.channel"
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
				class="fb-devices-module-channel-settings-channel-settings__add-item-row"
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
				:device="props.device"
				:channel="props.channelData.channel"
				:property="property"
			/>
		</fb-ui-items-container>
	</div>

	<property-settings-property-add-modal
		v-if="(activeView === ViewTypes.ADD_STATIC_PARAMETER || activeView === ViewTypes.ADD_DYNAMIC_PARAMETER) && newProperty !== null"
		:property="newProperty"
		:channel="props.channelData.channel"
		:device="props.device"
		@close="onCloseAddProperty"
	/>

	<div
		v-if="[FbFormResultTypes.WORKING, FbFormResultTypes.OK, FbFormResultTypes.ERROR].includes(props.remoteFormResult)"
		class="fb-devices-module-channel-settings-channel-settings__result"
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
import { useField, useFieldError, useForm } from 'vee-validate';
import * as yup from 'yup';
import get from 'lodash/get';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
	FbUiItemsContainer,
	FbUiContent,
	FbUiButton,
	FbUiDivider,
	FbUiLoadingBox,
	FbUiResultOk,
	FbUiResultErr,
	FbSizeTypes,
	FbUiButtonVariantTypes,
	FbUiDividerVariantTypes,
	FbFormResultTypes,
} from '@fastybird/web-ui-library';
import { DataType, PropertyType } from '@fastybird/metadata-library';

import { useEntityTitle, useFlashMessage, useUuid } from '@/lib/composables';
import { useChannels, useChannelProperties } from '@/lib/models';
import { IChannel, IChannelProperty, IDevice } from '@/lib/models/types';
import {
	ChannelSettingsChannelRename,
	PropertySettingsProperty,
	PropertySettingsPropertyAddModal,
	PropertySettingsVariablePropertiesEdit,
} from '@/lib/components';
import { IChannelData } from '@/types/devices-module';

enum ViewTypes {
	NONE = 'none',
	ADD_STATIC_PARAMETER = 'addStaticParameter',
	ADD_DYNAMIC_PARAMETER = 'addDynamicParameter',
}

interface IDeviceSettingsChannelRenameProps {
	device: IDevice;
	channelData: IChannelData;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FbFormResultTypes;
	remoteFormReset?: boolean;
}

const props = withDefaults(defineProps<IDeviceSettingsChannelRenameProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FbFormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FbFormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
	(e: 'created', channel: IChannel): void;
}>();

const { t } = useI18n();

const { generate: generateUuid } = useUuid();
const flashMessage = useFlashMessage();

const channelsStore = useChannels();
const propertiesStore = useChannelProperties();

const activeView = ref<ViewTypes>(ViewTypes.NONE);

const variableProperties = computed<IChannelProperty[]>((): IChannelProperty[] => {
	return props.channelData.properties.filter((property) => property.type.type === PropertyType.VARIABLE);
});

const dynamicProperties = computed<IChannelProperty[]>((): IChannelProperty[] => {
	return props.channelData.properties.filter((property) => property.type.type === PropertyType.DYNAMIC);
});

const newPropertyId = ref<string | null>(null);
const newProperty = computed<IChannelProperty | null>((): IChannelProperty | null =>
	newPropertyId.value ? propertiesStore.findById(newPropertyId.value) : null
);

interface IChannelSettingsChannelSettingsForm {
	about: {
		identifier: string;
		name: string | null;
		comment: string | null;
	};
	properties: {
		static: { id: string; value: string | null }[];
	};
}

const { validate } = useForm<IChannelSettingsChannelSettingsForm>({
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
			identifier: props.channelData.channel.identifier,
			name: props.channelData.channel.name,
			comment: props.channelData.channel.comment,
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

const onOpenView = async (view: ViewTypes): Promise<void> => {
	if (view === ViewTypes.ADD_STATIC_PARAMETER) {
		const { id } = await propertiesStore.add({
			channel: props.channelData.channel,
			type: { source: props.channelData.channel.type.source, type: PropertyType.VARIABLE, parent: 'channel' },
			draft: true,
			data: {
				identifier: generateUuid(),
				dataType: DataType.UNKNOWN,
			},
		});

		newPropertyId.value = id;
	} else if (view === ViewTypes.ADD_DYNAMIC_PARAMETER) {
		const { id } = await propertiesStore.add({
			channel: props.channelData.channel,
			type: { source: props.channelData.channel.type.source, type: PropertyType.DYNAMIC, parent: 'channel' },
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

				if (props.channelData.channel.draft) {
					const errorMessage = t('messages.notCreated', {
						device: useEntityTitle(props.device).value,
						channel: useEntityTitle(props.channelData.channel).value,
					});

					const variablePropertiesToCreate = variableProperties.value;
					const dynamicPropertiesToCreate = dynamicProperties.value;

					try {
						await channelsStore.edit({
							id: props.channelData.channel.id,
							data: {
								identifier: aboutField.value.identifier,
								name: aboutField.value.name,
								comment: aboutField.value.comment,
							},
						});

						await channelsStore.save({ id: props.channelData.channel.id });
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

							emit('created', props.channelData.channel);
						}, 2000);
					}
				} else {
					const errorMessage = t('messages.notEdited', {
						device: useEntityTitle(props.device).value,
						channel: useEntityTitle(props.channelData.channel).value,
					});

					try {
						await channelsStore.edit({
							id: props.channelData.channel.id,
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
@import 'channel-settings-channel-settings';
</style>

<i18n>
{
  "en": {
    "headings": {
      "aboutChannel": "About channel",
      "variableProperties": "Channel config parameters",
      "dynamicProperties": "Channel data parameters"
    },
    "buttons": {
      "addProperty": {
        "title": "Add parameter"
      }
    },
    "messages": {
      "savingData": "Saving channel",
      "notCreated": "New channel couldn't be created.",
      "notEdited": "Channel {channel} couldn't be edited."
    },
    "fields": {
      "identifier": {
        "validation": {
          "required": "Please fill in channel identifier"
        }
      }
    }
  }
}
</i18n>
