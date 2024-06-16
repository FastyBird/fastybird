<template>
	<el-form
		ref="propertyFormEl"
		:model="propertyForm"
		:rules="rules"
		:label-width="180"
		:label-position="'right'"
		status-icon
	>
		<el-form-item
			:label="t('fields.properties.identifier.title')"
			prop="identifier"
		>
			<el-input
				v-model="propertyForm.identifier"
				name="identifier"
				:readonly="!props.property.draft"
			/>
		</el-form-item>

		<el-form-item
			:label="t('fields.properties.name.title')"
			prop="name"
		>
			<el-input
				v-model="propertyForm.name"
				name="name"
			/>
		</el-form-item>

		<template v-if="'parent' in props.property && props.property.parent === null">
			<el-form-item
				:label="t('fields.properties.dataType.title')"
				prop="dataType"
			>
				<el-select
					v-model="propertyForm.dataType"
					name="dataType"
					:readonly="!props.property.draft"
				>
					<el-option
						v-for="item in dataTypeOptions"
						:key="item.value"
						:label="item.name"
						:value="item.value"
					/>
				</el-select>
			</el-form-item>

			<el-form-item
				:label="t('fields.properties.unit.title')"
				prop="unit"
			>
				<el-input
					v-model="propertyForm.unit"
					name="unit"
				/>
			</el-form-item>

			<template v-if="props.property.type.type === PropertyType.DYNAMIC">
				<el-form-item
					:label="t('fields.properties.settable.title')"
					prop="settable"
				>
					<el-switch
						v-model="propertyForm.settable"
						name="settable"
					/>
				</el-form-item>

				<el-form-item
					:label="t('fields.properties.queryable.title')"
					prop="queryable"
				>
					<el-switch
						v-model="propertyForm.queryable"
						name="queryable"
					/>
				</el-form-item>
			</template>

			<el-form-item
				:label="t('fields.properties.invalid.title')"
				prop="invalid"
			>
				<el-input
					v-model="propertyForm.invalid"
					name="invalid"
				/>
			</el-form-item>

			<el-form-item
				:label="t('fields.properties.scale.title')"
				prop="scale"
			>
				<el-input
					v-model="propertyForm.scale"
					name="scale"
				/>
			</el-form-item>

			<el-form-item
				:label="t('fields.properties.format.title')"
				prop="format"
			>
				<el-input
					v-model="propertyForm.format"
					name="format"
				/>
			</el-form-item>
		</template>
	</el-form>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import get from 'lodash.get';
import { ElForm, ElFormItem, ElInput, ElOption, ElSelect, ElSwitch, FormInstance, FormRules } from 'element-plus';

import { DataType, PropertyType } from '@fastybird/metadata-library';

import { useFlashMessage } from '../../composables';
import { useChannelProperties, useConnectorProperties, useDeviceProperties } from '../../models';
import { FormResultTypes } from '../../types';
import { IPropertySettingsPropertyFormForm, IPropertySettingsPropertyFormProps } from './property-settings-property-form.types';

defineOptions({
	name: 'PropertySettingsPropertyForm',
});

const props = withDefaults(defineProps<IPropertySettingsPropertyFormProps>(), {
	connector: undefined,
	device: undefined,
	channel: undefined,
	remoteFormSubmit: false,
	remoteFormReset: false,
	remoteFormResult: FormResultTypes.NONE,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
	(e: 'added'): void;
	(e: 'saved'): void;
}>();

const { t } = useI18n();

const flashMessage = useFlashMessage();

const channelPropertiesStore = useChannelProperties();
const connectorPropertiesStore = useConnectorProperties();
const devicePropertiesStore = useDeviceProperties();

const propertyFormEl = ref<FormInstance | undefined>(undefined);

const isConnectorProperty = computed<boolean>((): boolean => props.connector !== undefined);
const isDeviceProperty = computed<boolean>((): boolean => props.device !== undefined && props.channel === undefined);
const isChannelProperty = computed<boolean>((): boolean => props.device !== undefined && props.channel !== undefined);

const rules = reactive<FormRules<IPropertySettingsPropertyFormForm>>({
	identifier: [{ type: 'string', required: true, message: t('fields.properties.identifier.validation.required'), trigger: 'change' }],
	name: [{ type: 'string', required: false }],
	settable: [{ type: 'boolean', required: false }],
	queryable: [{ type: 'boolean', required: false }],
	dataType: [{ type: 'string', required: false, enum: Object.values(DataType) }],
	unit: [{ type: 'string', required: false }],
	invalid: [{ type: 'string', required: false }],
	scale: [{ type: 'number', required: false }],
	format: [{ type: 'string', required: false }],
});

const propertyForm = reactive<IPropertySettingsPropertyFormForm>({
	identifier: props.property.identifier,
	name: props.property.name,
	settable: props.property.settable,
	queryable: props.property.queryable,
	dataType: props.property.dataType,
	unit: props.property.unit,
	invalid: props.property.invalid as string,
	scale: props.property.scale,
	format: props.property.format ? JSON.stringify(props.property.format) : null,
});

const dataTypeOptions: { name: string; value: string }[] = [
	{
		name: t('fields.properties.dataType.values.unknown'),
		value: DataType.UNKNOWN,
	},
	{
		name: t('fields.properties.dataType.values.char'),
		value: DataType.CHAR,
	},
	{
		name: t('fields.properties.dataType.values.uchar'),
		value: DataType.UCHAR,
	},
	{
		name: t('fields.properties.dataType.values.short'),
		value: DataType.SHORT,
	},
	{
		name: t('fields.properties.dataType.values.ushort'),
		value: DataType.USHORT,
	},
	{
		name: t('fields.properties.dataType.values.int'),
		value: DataType.INT,
	},
	{
		name: t('fields.properties.dataType.values.uint'),
		value: DataType.UINT,
	},
	{
		name: t('fields.properties.dataType.values.float'),
		value: DataType.FLOAT,
	},
	{
		name: t('fields.properties.dataType.values.bool'),
		value: DataType.BOOLEAN,
	},
	{
		name: t('fields.properties.dataType.values.string'),
		value: DataType.STRING,
	},
	{
		name: t('fields.properties.dataType.values.enum'),
		value: DataType.ENUM,
	},
	{
		name: t('fields.properties.dataType.values.date'),
		value: DataType.DATE,
	},
	{
		name: t('fields.properties.dataType.values.time'),
		value: DataType.TIME,
	},
	{
		name: t('fields.properties.dataType.values.datetime'),
		value: DataType.DATETIME,
	},
	{
		name: t('fields.properties.dataType.values.color'),
		value: DataType.COLOR,
	},
	{
		name: t('fields.properties.dataType.values.button'),
		value: DataType.BUTTON,
	},
	{
		name: t('fields.properties.dataType.values.switch'),
		value: DataType.SWITCH,
	},
];

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

			await propertyFormEl.value!.validate(async (valid: boolean): Promise<void> => {
				if (!valid) {
					return;
				}

				const data = {
					identifier: propertyForm.identifier,
					name: propertyForm.name,
					settable: propertyForm.settable,
					queryable: propertyForm.queryable,
					dataType: propertyForm.dataType,
					unit: propertyForm.unit,
					invalid: propertyForm.invalid,
					scale: propertyForm.scale,
					format: propertyForm.format ? JSON.parse(propertyForm.format) : null,
				};

				try {
					if (props.property.draft) {
						if (isChannelProperty.value) {
							await channelPropertiesStore.edit({
								id: props.property.id,
								data,
							});

							if (!props.channel?.draft) {
								emit('update:remoteFormResult', FormResultTypes.WORKING);

								await channelPropertiesStore.save({ id: props.property.id });
							}
						} else if (isDeviceProperty.value) {
							await devicePropertiesStore.edit({
								id: props.property.id,
								data,
							});

							if (!props.device?.draft) {
								emit('update:remoteFormResult', FormResultTypes.WORKING);

								await devicePropertiesStore.save({ id: props.property.id });
							}
						} else if (isConnectorProperty.value) {
							await connectorPropertiesStore.edit({
								id: props.property.id,
								data,
							});

							if (!props.connector?.draft) {
								emit('update:remoteFormResult', FormResultTypes.WORKING);

								await connectorPropertiesStore.save({ id: props.property.id });
							}
						}
					} else {
						emit('update:remoteFormResult', FormResultTypes.WORKING);

						if (isChannelProperty.value) {
							await channelPropertiesStore.edit({
								id: props.property.id,
								data,
							});
						} else if (isDeviceProperty.value) {
							await devicePropertiesStore.edit({
								id: props.property.id,
								data,
							});
						} else if (isConnectorProperty.value) {
							await connectorPropertiesStore.edit({
								id: props.property.id,
								data,
							});
						}
					}

					emit('update:remoteFormResult', FormResultTypes.OK);

					if (isChannelProperty.value) {
						if (!props.channel?.draft) {
							timer = window.setTimeout((): void => {
								clearResult();
								emit('saved');
							}, 2000);
						} else {
							clearResult();
							emit('added');
						}
					} else if (isDeviceProperty.value) {
						if (!props.device?.draft) {
							timer = window.setTimeout((): void => {
								clearResult();
								emit('saved');
							}, 2000);
						} else {
							clearResult();
							emit('added');
						}
					} else if (isConnectorProperty.value) {
						if (!props.connector?.draft) {
							timer = window.setTimeout((): void => {
								clearResult();
								emit('saved');
							}, 2000);
						} else {
							clearResult();
							emit('added');
						}
					}
				} catch (e: any) {
					const errorMessage = props.property.draft
						? t('messages.properties.propertyNotCreated', { property: propertyForm.name ?? propertyForm.identifier })
						: t('messages.properties.propertyNotEdited', { property: propertyForm.name ?? propertyForm.identifier });

					if (get(e, 'exception', null) !== null) {
						flashMessage.exception(get(e, 'exception', null), errorMessage);
					} else {
						flashMessage.error(errorMessage);
					}

					emit('update:remoteFormResult', FormResultTypes.ERROR);

					timer = window.setTimeout(clearResult, 2000);
				}
			});
		}
	}
);

watch(
	(): boolean => props.remoteFormReset,
	(val): void => {
		emit('update:remoteFormReset', false);

		if (val) {
			propertyForm.identifier = props.property.identifier;
			propertyForm.name = props.property.name ?? null;
			propertyForm.settable = props.property.settable;
			propertyForm.queryable = props.property.queryable;
			propertyForm.dataType = props.property.dataType;
			propertyForm.unit = props.property.unit ?? null;
			propertyForm.invalid = props.property.invalid as null;
			propertyForm.scale = props.property.scale ?? null;
			propertyForm.format = props.property.format ? JSON.stringify(props.property.format) : null;
		}
	}
);
</script>
