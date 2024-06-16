<template>
	<el-dialog
		ref="dialogRef"
		v-model="dialogVisible"
		:show-close="false"
		:fullscreen="isExtraSmallDevice"
		:draggable="!isExtraSmallDevice"
		@close="onClose"
	>
		<template #header>
			<fb-dialog-header
				:layout="isExtraSmallDevice ? 'phone' : 'default'"
				:left-btn-label="t('buttons.close.title')"
				:right-btn-label="t('buttons.save.title')"
				@close="onClose"
			>
				<template #title>
					{{ t('headings.accountSettings') }}
				</template>

				<template #icon>
					<fas-user />
				</template>
			</fb-dialog-header>
		</template>

		<settings-account-form
			v-if="sessionStore.account"
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			:account="sessionStore.account"
		/>

		<template #footer>
			<fb-dialog-footer
				:layout="isExtraSmallDevice ? 'phone' : 'default'"
				:left-btn-label="t('buttons.close.title')"
				:right-btn-label="t('buttons.save.title')"
				@left-click="onClose"
				@right-click="onSave"
			/>
		</template>
	</el-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElDialog, ElLoading } from 'element-plus';

import { FbDialogHeader, FbDialogFooter } from '@fastybird/web-ui-library';
import { FasUser } from '@fastybird/web-ui-icons';

import { useBreakpoints } from '../../composables';
import { useSession } from '../../models';
import { FormResultTypes } from '../../types';
import { ISettingsAccountDialogProps } from './settings-account-dialog.types';
import SettingsAccountForm from './settings-account-form.vue';

defineOptions({
	name: 'SettingsAccountDialog',
});

const props = defineProps<ISettingsAccountDialogProps>();

const emit = defineEmits<{
	(e: 'update:visible', visibility: boolean): void;
	(e: 'close'): void;
}>();

const { t } = useI18n();
const { isExtraSmallDevice } = useBreakpoints();

const sessionStore = useSession();

const dialogRef = ref();

const dialogVisible = ref<boolean>(props.visible);
const loading = ref<any | undefined>(undefined);

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FormResultTypes>(FormResultTypes.NONE);

const onClose = (): void => {
	emit('update:visible', false);
	emit('close');
};

const onSave = (): void => {
	remoteFormSubmit.value = true;
};

watch(
	(): FormResultTypes => remoteFormResult.value,
	(actual: FormResultTypes, previous: FormResultTypes): void => {
		if (actual === FormResultTypes.WORKING) {
			loading.value = ElLoading.service({
				target: dialogRef.value.$el.nextElementSibling.querySelector('.el-dialog'),
			});
		} else {
			(loading.value as any)?.close();
		}

		if (actual === FormResultTypes.NONE && previous === FormResultTypes.OK) {
			emit('close');
		}
	}
);
</script>
