<template>
	<el-form
		ref="deviceFormEl"
		:model="deviceForm"
		:rules="rules"
		label-position="top"
		status-icon
		class="px-5 py-5"
	>
		<h3>
			{{ t('headings.devices.aboutDevice') }}
		</h3>

		<device-settings-device-rename
			v-model="deviceForm.about"
			:device="props.deviceData.device"
		/>

		<property-settings-variable-properties-edit
			v-model="deviceForm.properties.static"
			:properties="variableProperties"
		/>
	</el-form>

	<template v-if="!props.deviceData.device.draft">
		<el-divider />

		<fb-list class="pb-2">
			<template #title>
				{{ t('headings.devices.channels') }}
			</template>

			<template
				v-if="channelsData.length > 0"
				#buttons
			>
				<el-button
					:icon="FasPlus"
					type="primary"
					size="small"
					plain
					@click="emit('addChannel', $event)"
				>
					{{ t('buttons.addChannel.title') }}
				</el-button>
			</template>

			<div
				v-if="channelsData.length === 0"
				class="p-2"
			>
				<el-button
					:icon="FasPlus"
					size="large"
					plain
					class="w-full"
					@click="emit('addChannel', $event)"
				>
					<span>{{ t('buttons.addChannel.title') }}</span>
				</el-button>
			</div>

			<device-settings-device-channel
				v-for="channelData in channelsData"
				:key="channelData.channel.id"
				:device="props.deviceData.device"
				:channel-data="channelData"
				@edit="emit('editChannel', channelData.channel.id, $event)"
				@remove="emit('removeChannel', channelData.channel.id, $event)"
				@reset="emit('resetChannel', channelData.channel.id, $event)"
			/>
		</fb-list>
	</template>

	<el-divider />

	<fb-list class="pb-2">
		<template #title>
			{{ t('headings.devices.variableProperties') }}
		</template>

		<template
			v-if="variableProperties.length > 0"
			#buttons
		>
			<el-button
				:icon="FasPlus"
				type="primary"
				size="small"
				plain
				@click="emit('addStaticProperty', $event)"
			>
				{{ t('buttons.addProperty.title') }}
			</el-button>
		</template>

		<div
			v-if="variableProperties.length === 0"
			class="p-2"
		>
			<el-button
				:icon="FasPlus"
				size="large"
				class="w-full"
				@click="emit('addStaticProperty', $event)"
			>
				<span>{{ t('buttons.addProperty.title') }}</span>
			</el-button>
		</div>

		<property-settings-property
			v-for="property in variableProperties"
			:key="property.identifier"
			:device="props.deviceData.device"
			:property="property"
			@edit="emit('editProperty', property.id, $event)"
			@remove="emit('removeProperty', property.id, $event)"
		/>
	</fb-list>

	<el-divider />

	<fb-list class="pb-2">
		<template #title>
			{{ t('headings.devices.dynamicProperties') }}
		</template>

		<template
			v-if="dynamicProperties.length > 0"
			#buttons
		>
			<el-button
				:icon="FasPlus"
				type="primary"
				size="small"
				plain
				@click="emit('addDynamicProperty', $event)"
			>
				{{ t('buttons.addProperty.title') }}
			</el-button>
		</template>

		<div
			v-if="dynamicProperties.length === 0"
			class="p-2"
		>
			<el-button
				:icon="FasPlus"
				size="large"
				class="w-full"
				@click="emit('addDynamicProperty', $event)"
			>
				<span>{{ t('buttons.addProperty.title') }}</span>
			</el-button>
		</div>

		<property-settings-property
			v-for="property in dynamicProperties"
			:key="property.identifier"
			:device="props.deviceData.device"
			:property="property"
			@edit="emit('editProperty', property.id, $event)"
			@remove="emit('removeProperty', property.id, $event)"
		/>
	</fb-list>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { orderBy } from 'natural-orderby';
import get from 'lodash/get';
import { ElButton, ElDivider, ElForm, FormInstance, FormRules } from 'element-plus';

import { FasPlus } from '@fastybird/web-ui-icons';
import { FbList } from '@fastybird/web-ui-library';
import { PropertyType } from '@fastybird/metadata-library';

import { useEntityTitle, useFlashMessage } from '../../composables';
import { useDevices, useDeviceProperties } from '../../models';
import { IDevice, IDeviceProperty } from '../../models/types';
import {
	DeviceSettingsDeviceChannel,
	DeviceSettingsDeviceRename,
	PropertySettingsProperty,
	PropertySettingsVariablePropertiesEdit,
} from '../../components';
import { FormResultTypes, IChannelData, IConnectorSettingsConnectorSettingsForm } from '../../types';
import { IDeviceSettingsDeviceSettingsForm, IDeviceSettingsDeviceSettingsProps } from './device-settings-device-settings.types';

defineOptions({
	name: 'DeviceSettingsDeviceSettings',
});

const props = withDefaults(defineProps<IDeviceSettingsDeviceSettingsProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
	(e: 'addChannel', event: Event): void;
	(e: 'editChannel', id: string, event: Event): void;
	(e: 'removeChannel', id: string, event: Event): void;
	(e: 'resetChannel', id: string, event: Event): void;
	(e: 'addStaticProperty', event: Event): void;
	(e: 'addDynamicProperty', event: Event): void;
	(e: 'editProperty', id: string, event: Event): void;
	(e: 'removeProperty', id: string, event: Event): void;
	(e: 'created', device: IDevice): void;
}>();

const { t } = useI18n();

const flashMessage = useFlashMessage();

const devicesStore = useDevices();
const propertiesStore = useDeviceProperties();

const deviceFormEl = ref<FormInstance | undefined>(undefined);

const variableProperties = computed<IDeviceProperty[]>((): IDeviceProperty[] => {
	return props.deviceData.properties.filter((property) => property.type.type === PropertyType.VARIABLE);
});

const dynamicProperties = computed<IDeviceProperty[]>((): IDeviceProperty[] => {
	return props.deviceData.properties.filter((property) => property.type.type === PropertyType.DYNAMIC);
});

const rules = reactive<FormRules<IConnectorSettingsConnectorSettingsForm>>({
	about: {
		type: 'object',
		required: true,
		fields: {
			identifier: [{ type: 'string', required: true, message: t('fields.devices.identifier.validation.required') }],
			name: [{ type: 'string', required: false }],
			comment: [{ type: 'string', required: false }],
		},
	},
	properties: {
		type: 'object',
		required: true,
		fields: {
			static: {
				type: 'array',
				required: true,
				fields: {
					id: [{ type: 'string', required: true, message: t('fields.devices.identifier.validation.required') }],
					value: [{ required: false }],
				},
			},
		},
	},
});

const deviceForm = reactive<IDeviceSettingsDeviceSettingsForm>({
	about: {
		identifier: props.deviceData.device.identifier,
		name: props.deviceData.device.name,
		comment: props.deviceData.device.comment,
	},
	properties: {
		static: variableProperties.value.map((property) => ({ id: property.id, value: property.value })),
	},
});

let timer: number;

const channelsData = computed<IChannelData[]>((): IChannelData[] => {
	return orderBy<IChannelData>(
		props.deviceData.channels,
		[(v): string => v.channel.name ?? v.channel.identifier, (v): string => v.channel.identifier],
		['asc']
	);
});

const clearResult = (): void => {
	window.clearTimeout(timer);

	emit('update:remoteFormResult', FormResultTypes.NONE);
};

watch(
	(): boolean => props.remoteFormSubmit,
	async (val: boolean): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			await deviceFormEl.value!.validate(async (valid: boolean): Promise<void> => {
				if (!valid) {
					return;
				}

				if (props.deviceData.device.draft) {
					const errorMessage = t('messages.notCreated', {
						device: useEntityTitle(props.deviceData.device).value,
					});

					emit('update:remoteFormResult', FormResultTypes.WORKING);

					const variablePropertiesToCreate = variableProperties.value;
					const dynamicPropertiesToCreate = dynamicProperties.value;

					try {
						await devicesStore.edit({
							id: props.deviceData.device.id,
							data: {
								identifier: deviceForm.about.identifier,
								name: deviceForm.about.name,
								comment: deviceForm.about.comment,
							},
						});

						await devicesStore.save({ id: props.deviceData.device.id });
					} catch (e: any) {
						if (get(e, 'exception', null) !== null) {
							flashMessage.exception(get(e, 'exception', null), t('messages.devices.notCreated'));
						} else {
							flashMessage.error(t('messages.devices.notCreated'));
						}

						emit('update:remoteFormResult', FormResultTypes.ERROR);

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

							emit('update:remoteFormResult', FormResultTypes.ERROR);

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

							emit('update:remoteFormResult', FormResultTypes.ERROR);

							success = false;

							break;
						}
					}

					for (const variablePropertyField of deviceForm.properties.static) {
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

							emit('update:remoteFormResult', FormResultTypes.ERROR);

							timer = window.setTimeout(clearResult, 2000);

							success = false;

							break;
						}
					}

					if (success) {
						emit('update:remoteFormResult', FormResultTypes.OK);

						timer = window.setTimeout((): void => {
							clearResult();

							emit('created', props.deviceData.device);
						}, 2000);
					}
				} else {
					const errorMessage = t('messages.devices.notEdited', {
						device: useEntityTitle(props.deviceData.device).value,
					});

					emit('update:remoteFormResult', FormResultTypes.WORKING);

					try {
						await devicesStore.edit({
							id: props.deviceData.device.id,
							data: {
								name: deviceForm.about.name,
								comment: deviceForm.about.comment,
							},
						});
					} catch (e: any) {
						if (get(e, 'exception', null) !== null) {
							flashMessage.exception(get(e, 'exception', null), errorMessage);
						} else {
							flashMessage.error(errorMessage);
						}

						emit('update:remoteFormResult', FormResultTypes.ERROR);

						timer = window.setTimeout(clearResult, 2000);

						return;
					}

					let success = true;

					for (const variablePropertyField of deviceForm.properties.static) {
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

							emit('update:remoteFormResult', FormResultTypes.ERROR);

							timer = window.setTimeout(clearResult, 2000);

							success = false;

							break;
						}
					}

					if (success) {
						emit('update:remoteFormResult', FormResultTypes.OK);

						timer = window.setTimeout(clearResult, 2000);
					}
				}
			});
		}
	}
);

watch(
	(): boolean => props.remoteFormReset,
	(val: boolean): void => {
		emit('update:remoteFormReset', false);

		if (val) {
			deviceForm.about.identifier = props.deviceData.device.identifier;
			deviceForm.about.name = props.deviceData.device.name;
			deviceForm.about.comment = props.deviceData.device.comment;
		}
	}
);
</script>
