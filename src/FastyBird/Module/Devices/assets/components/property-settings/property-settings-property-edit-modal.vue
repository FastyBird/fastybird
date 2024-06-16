<template>
	<el-dialog
		v-model="open"
		:show-close="false"
		align-center
		@closed="onClosed"
	>
		<template #header>
			<fb-dialog-header
				:layout="isXSDevice ? 'phone' : isSMDevice ? 'tablet' : 'default'"
				:left-btn-label="t('buttons.close.title')"
				:right-btn-label="isDraft ? t('buttons.update.title') : t('buttons.save.title')"
				:icon="FasPencil"
				@left-click="onClose"
				@right-click="onSubmitForm"
				@close="onClose"
			>
				<template #title>
					{{ t('headings.properties.edit') }}
				</template>
			</fb-dialog-header>
		</template>

		<property-settings-property-form
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			:connector="props.connector"
			:device="props.device"
			:channel="props.channel"
			:property="props.property"
			@created="onCreated"
		/>

		<template #footer>
			<fb-dialog-footer
				:left-btn-label="t('buttons.close.title')"
				:right-btn-label="isDraft ? t('buttons.update.title') : t('buttons.save.title')"
				@left-click="onClose"
				@right-click="onSubmitForm"
			/>
		</template>
	</el-dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElDialog } from 'element-plus';

import { FasPencil } from '@fastybird/web-ui-icons';
import { FbDialogFooter, FbDialogHeader } from '@fastybird/web-ui-library';

import { useBreakpoints } from '../../composables';
import { PropertySettingsPropertyForm } from '../../components';
import { FormResultTypes } from '../../types';
import { IPropertySettingsPropertyEditModalProps } from './property-settings-property-edit-modal.types';

defineOptions({
	name: 'PropertySettingsPropertyEditModal',
});

const props = defineProps<IPropertySettingsPropertyEditModalProps>();

const emit = defineEmits<{
	(e: 'close'): void;
}>();

const { t } = useI18n();
const { isXSDevice, isSMDevice } = useBreakpoints();

const open = ref<boolean>(true);

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FormResultTypes>(FormResultTypes.NONE);

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

const onSubmitForm = (): void => {
	remoteFormSubmit.value = true;
};

const onClose = (): void => {
	open.value = false;
};

const onClosed = (): void => {
	emit('close');
};

const onCreated = (): void => {
	onClose();
};

watch(
	(): FormResultTypes => remoteFormResult.value,
	(actual, previous): void => {
		if (actual === FormResultTypes.NONE && previous === FormResultTypes.OK) {
			onClose();
		}
	}
);
</script>
