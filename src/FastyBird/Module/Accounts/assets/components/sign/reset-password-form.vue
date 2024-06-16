<template>
	<el-form
		ref="resetPasswordFormEl"
		:model="resetPasswordForm"
		:rules="rules"
		label-position="top"
		status-icon
		class="px-5"
		@submit.prevent="onSubmit"
	>
		<el-form-item
			:label="t('fields.identity.uid.title')"
			prop="uid"
			class="mb-10"
		>
			<el-input
				v-model="resetPasswordForm.uid"
				name="uid"
			/>
		</el-form-item>

		<el-button
			type="primary"
			size="large"
			class="block w-full"
			@click="onSubmit(resetPasswordFormEl)"
		>
			{{ t('buttons.resetPassword.title') }}
		</el-button>
	</el-form>

	<div class="mt-5 px-5">
		<el-button
			class="block w-full"
			@click="onBackToSignIn()"
		>
			{{ t('buttons.backToSignIn.title') }}
		</el-button>
	</div>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import get from 'lodash/get';
import { ElButton, ElForm, ElFormItem, ElInput, FormInstance, FormRules } from 'element-plus';

import { useAccount } from '../../models';
import { useFlashMessage, useRoutesNames } from '../../composables';
import { FormResultTypes } from '../../types';
import { IResetPasswordForm, IResetPasswordProps } from './reset-password-form.types';

defineOptions({
	name: 'ResetPasswordForm',
});

const props = withDefaults(defineProps<IResetPasswordProps>(), {
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<{
	(e: 'update:remoteFormResult', remoteFormResult: FormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
}>();

const { t } = useI18n();
const flashMessage = useFlashMessage();
const router = useRouter();

const accountStore = useAccount();
const { routeNames } = useRoutesNames();

const resetPasswordFormEl = ref<FormInstance | undefined>(undefined);

const rules = reactive<FormRules<IResetPasswordForm>>({
	uid: [{ required: true, message: t('fields.identity.uid.validation.required'), trigger: 'change' }],
});

const resetPasswordForm = reactive<IResetPasswordForm>({
	uid: '',
});

const onSubmit = async (formEl: FormInstance | undefined): Promise<void> => {
	if (!formEl) return;

	await formEl.validate(async (valid: boolean): Promise<void> => {
		if (valid) {
			emit('update:remoteFormResult', FormResultTypes.WORKING);

			try {
				await accountStore.requestReset({ uid: resetPasswordForm.uid });

				emit('update:remoteFormResult', FormResultTypes.OK);
			} catch (e: any) {
				emit('update:remoteFormResult', FormResultTypes.ERROR);

				const errorMessage = t('messages.requestError');

				if (get(e, 'exception', null) !== null) {
					flashMessage.exception(e.exception, errorMessage);
				} else if (get(e, 'response', null) !== null) {
					flashMessage.requestError(e.response, errorMessage);
				} else {
					flashMessage.error(errorMessage);
				}
			}
		}
	});
};

const onBackToSignIn = (): void => {
	router.push({ name: routeNames.signIn });
};

watch(
	(): boolean => props.remoteFormReset,
	(val): void => {
		emit('update:remoteFormReset', false);

		if (val) {
			if (!resetPasswordFormEl.value) return;

			resetPasswordFormEl.value.resetFields();
		}
	}
);
</script>
