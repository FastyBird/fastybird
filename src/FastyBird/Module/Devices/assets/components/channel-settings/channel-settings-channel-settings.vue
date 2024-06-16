<template>
	<el-form
		ref="channelFormEl"
		:model="channelForm"
		:rules="rules"
		label-position="top"
		status-icon
		class="px-5 py-5"
	>
		<h3>
			{{ t('headings.channels.aboutChannel') }}
		</h3>

		<channel-settings-channel-rename
			v-model="channelForm.about"
			:channel="props.channelData.channel"
		/>

		<property-settings-variable-properties-edit
			v-model="channelForm.properties.static"
			:properties="variableProperties"
		/>
	</el-form>

	<el-divider />

	<fb-list class="pb-2">
		<template #title>
			{{ t('headings.channels.variableProperties') }}
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
			:channel="props.channelData.channel"
			:property="property"
			@edit="emit('editProperty', property.id, $event)"
			@remove="emit('removeProperty', property.id, $event)"
		/>
	</fb-list>

	<el-divider />

	<fb-list class="pb-2">
		<template #title>
			{{ t('headings.channels.dynamicProperties') }}
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
			:channel="props.channelData.channel"
			:property="property"
			@edit="emit('editProperty', property.id, $event)"
			@remove="emit('removeProperty', property.id, $event)"
		/>
	</fb-list>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import get from 'lodash/get';
import { ElButton, ElDivider, ElForm, FormInstance, FormRules } from 'element-plus';

import { FbList } from '@fastybird/web-ui-library';
import { FasPlus } from '@fastybird/web-ui-icons';
import { PropertyType } from '@fastybird/metadata-library';

import { useEntityTitle, useFlashMessage } from '../../composables';
import { useChannels, useChannelProperties } from '../../models';
import { IChannel, IChannelProperty } from '../../models/types';
import { ChannelSettingsChannelRename, PropertySettingsProperty, PropertySettingsVariablePropertiesEdit } from '../../components';
import { FormResultTypes } from '../../types';
import { IChannelSettingsChannelSettingsForm, IChannelSettingsChannelSettingsProps } from './channel-settings-channel-settings.types';

defineOptions({
	name: 'ChannelSettingsChannelSettings',
});

const props = withDefaults(defineProps<IChannelSettingsChannelSettingsProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
	(e: 'addStaticProperty', event: Event): void;
	(e: 'addDynamicProperty', event: Event): void;
	(e: 'editProperty', id: string, event: Event): void;
	(e: 'removeProperty', id: string, event: Event): void;
	(e: 'created', channel: IChannel): void;
}>();

const { t } = useI18n();

const flashMessage = useFlashMessage();

const channelsStore = useChannels();
const propertiesStore = useChannelProperties();

const channelFormEl = ref<FormInstance | undefined>(undefined);

const variableProperties = computed<IChannelProperty[]>((): IChannelProperty[] => {
	return props.channelData.properties.filter((property) => property.type.type === PropertyType.VARIABLE);
});

const dynamicProperties = computed<IChannelProperty[]>((): IChannelProperty[] => {
	return props.channelData.properties.filter((property) => property.type.type === PropertyType.DYNAMIC);
});

const rules = reactive<FormRules<IChannelSettingsChannelSettingsForm>>({
	about: {
		type: 'object',
		required: true,
		fields: {
			identifier: [{ type: 'string', required: true, message: t('fields.channels.identifier.validation.required') }],
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
					id: [{ type: 'string', required: true, message: t('fields.channels.identifier.validation.required') }],
					value: [{ required: false }],
				},
			},
		},
	},
});

const channelForm = reactive<IChannelSettingsChannelSettingsForm>({
	about: {
		identifier: props.channelData.channel.identifier,
		name: props.channelData.channel.name,
		comment: props.channelData.channel.comment,
	},
	properties: {
		static: variableProperties.value.map((property) => ({ id: property.id, value: property.value })),
	},
});

let timer: number;

const clearResult = (): void => {
	window.clearTimeout(timer);

	emit('update:remoteFormResult', FormResultTypes.NONE);
};

watch(
	(): boolean => props.remoteFormSubmit,
	async (val: boolean): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			await channelFormEl.value!.validate(async (valid: boolean): Promise<void> => {
				if (!valid) {
					return;
				}

				emit('update:remoteFormResult', FormResultTypes.WORKING);

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
								identifier: channelForm.about.identifier,
								name: channelForm.about.name,
								comment: channelForm.about.comment,
							},
						});

						await channelsStore.save({ id: props.channelData.channel.id });
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

					for (const variablePropertyField of channelForm.properties.static) {
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
				} else {
					const errorMessage = t('messages.notEdited', {
						device: useEntityTitle(props.device).value,
						channel: useEntityTitle(props.channelData.channel).value,
					});

					try {
						await channelsStore.edit({
							id: props.channelData.channel.id,
							data: {
								name: channelForm.about.name,
								comment: channelForm.about.comment,
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

					for (const variablePropertyField of channelForm.properties.static) {
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
</script>
