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

	<fb-ui-content :mb="FbSizeTypes.MEDIUM">
		<fb-form-input
			v-model="password"
			:error="passwordError"
			:label="t('fields.identity.password.title')"
			:required="true"
			:type="FbFormInputTypeTypes.PASSWORD"
			name="password"
		/>
	</fb-ui-content>

	<fb-ui-content :mb="FbSizeTypes.MEDIUM">
		<fb-form-checkbox
			v-model="persistent"
			:option="true"
			name="persistent"
		>
			{{ t('fields.persistent.title') }}
		</fb-form-checkbox>
	</fb-ui-content>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useForm, useField } from 'vee-validate';
import * as yup from 'yup';
import get from 'lodash/get';

import { FbUiContent, FbFormInput, FbFormCheckbox, FbFormInputTypeTypes, FbSizeTypes, FbFormResultTypes } from '@fastybird/web-ui-library';

import { useSession } from '@/models';
import { useFlashMessage } from '@/composables';
import { ISignInForm, ISignInProps } from '@/components/sign/sign-in-form.types';

const props = withDefaults(defineProps<ISignInProps>(), {
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

const sessionStore = useSession();

const { validate } = useForm<ISignInForm>({
	validationSchema: yup.object({
		uid: yup.string().required(t('fields.identity.uid.validation.required')),
		password: yup.string().required(t('fields.identity.password.validation.required')),
		persistent: yup.boolean().default(false),
	}),
});

const { value: uid, errorMessage: uidError, setValue: setUid } = useField<string>('uid');
const { value: password, errorMessage: passwordError, setValue: setPassword } = useField<string>('password');
const { value: persistent, setValue: setPersistent } = useField<boolean>('persistent');

watch(
	(): boolean => props.remoteFormSubmit,
	async (val): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			const validationResult = await validate();

			if (validationResult.valid) {
				emit('update:remoteFormResult', FbFormResultTypes.WORKING);

				try {
					await sessionStore.create({ uid: uid.value, password: password.value });

					emit('update:remoteFormResult', FbFormResultTypes.OK);
				} catch (e: any) {
					emit('update:remoteFormResult', FbFormResultTypes.ERROR);

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
		}
	}
);

watch(
	(): boolean => props.remoteFormReset,
	(val): void => {
		emit('update:remoteFormReset', false);

		if (val) {
			setUid('');
			setPassword('');
			setPersistent(false);
		}
	}
);
</script>

<i18n src="@/locales/locales.json" />
