<template>
	<fb-ui-content :mb="FbSizeTypes.MEDIUM">
		<fb-form-input
			v-model="uid"
			:error="uidError"
			:label="t('fields.identity.uid.title')"
			:required="true"
			name="uid"
		/>
	</fb-ui-content>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useField, useForm } from 'vee-validate';
import * as yup from 'yup';
import get from 'lodash/get';

import { FbFormInput, FbFormResultTypes, FbSizeTypes, FbUiContent } from '@fastybird/web-ui-library';

import { useAccount } from '@/models';
import { useFlashMessage } from '@/composables';
import { IResetPasswordForm, IResetPasswordProps } from '@/types';

const props = withDefaults(defineProps<IResetPasswordProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FbFormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FbFormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
}>();

const { t } = useI18n();
const flashMessage = useFlashMessage();

const accountStore = useAccount();

const { validate } = useForm<IResetPasswordForm>({
	validationSchema: yup.object({
		uid: yup.string().required(t('fields.identity.uid.validation.required')),
	}),
});

const { value: uid, errorMessage: uidError, setValue: setUid } = useField<string>('uid');

watch(
	(): boolean => props.remoteFormSubmit,
	async (val): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			const validationResult = await validate();

			if (validationResult.valid) {
				emit('update:remoteFormResult', FbFormResultTypes.WORKING);

				try {
					await accountStore.requestReset({ uid: uid.value });

					emit('update:remoteFormResult', FbFormResultTypes.OK);
				} catch (e: any) {
					emit('update:remoteFormResult', FbFormResultTypes.ERROR);

					const errorMessage = t('messages.passwordRequestFail');

					if (get(e, 'exception', null) !== null) {
						flashMessage.exception(e.exception, errorMessage);
					} else if (get(e, 'response', null) !== null) {
						flashMessage.requestError(e.response, errorMessage);
					} else {
						flashMessage.error(errorMessage);
					}
				}
			}
		}
	}
);

watch(
	(): boolean => props.remoteFormReset,
	(val): void => {
		emit('update:remoteFormReset', false);

		if (val) {
			setUid('');
		}
	}
);
</script>

<i18n src="@/locales/locales.json" />
