<template>
	<fb-form-input
		v-model="currentPassword"
		:error="currentPasswordError"
		:label="t('fields.password.current.title')"
		:required="true"
		:type="FbFormInputTypeTypes.PASSWORD"
		name="currentPassword"
		spellcheck="false"
	>
		<template #help-line>
			{{ t('fields.password.current.help') }}
		</template>
	</fb-form-input>

	<fb-form-input
		v-model="newPassword"
		:error="newPasswordError"
		:label="t('fields.password.new.title')"
		:required="true"
		:type="FbFormInputTypeTypes.PASSWORD"
		name="newPassword"
		spellcheck="false"
	>
		<template #help-line>
			{{ t('fields.password.new.help') }}
		</template>
	</fb-form-input>

	<fb-form-input
		v-model="repeatPassword"
		:error="repeatPasswordError"
		:label="t('fields.password.repeat.title')"
		:required="true"
		:type="FbFormInputTypeTypes.PASSWORD"
		name="repeatPassword"
		spellcheck="false"
	>
		<template #help-line>
			{{ t('fields.password.repeat.help') }}
		</template>
	</fb-form-input>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { useForm, useField } from 'vee-validate';
import { object as yObject, string as yString } from 'yup';
import { useI18n } from 'vue-i18n';
import get from 'lodash/get';

import { FbFormInput, FbFormInputTypeTypes, FbFormResultTypes } from '@fastybird/web-ui-library';

import { useAccount } from '../../models';
import { useFlashMessage } from '../../composables';
import { ISettingsPasswordProps } from './settings-password-form.types';

const props = withDefaults(defineProps<ISettingsPasswordProps>(), {
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

interface ISettingsPasswordForm {
	currentPassword: string;
	newPassword: string;
	repeatPassword: string;
}

const { validate } = useForm<ISettingsPasswordForm>({
	validationSchema: yObject({
		currentPassword: yString().required(t('fields.password.current.validation.required')),
		newPassword: yString().required(t('fields.password.new.validation.required')),
		repeatPassword: yString().required(t('fields.password.repeat.validation.required')),
	}),
});

const { value: currentPassword, errorMessage: currentPasswordError, setValue: setCurrentPassword } = useField<string>('currentPassword');
const { value: newPassword, errorMessage: newPasswordError, setValue: setNewPassword } = useField<string>('newPassword');
const { value: repeatPassword, errorMessage: repeatPasswordError, setValue: setRepeatPassword } = useField<string>('repeatPassword');

let timer: number;

const clearResult = (): void => {
	window.clearTimeout(timer);

	emit('update:remoteFormResult', FbFormResultTypes.NONE);
};

watch(
	(): boolean => props.remoteFormSubmit,
	async (val): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			const validationResult = await validate();

			if (validationResult.valid) {
				emit('update:remoteFormResult', FbFormResultTypes.WORKING);

				const errorMessage = t('messages.passwordNotEdited');

				try {
					await accountStore.editIdentity({
						id: props.identity.id,
						data: {
							password: {
								current: currentPassword.value,
								new: newPassword.value,
							},
						},
					});

					emit('update:remoteFormResult', FbFormResultTypes.OK);

					timer = window.setTimeout(clearResult, 2000);
				} catch (e: any) {
					if (get(e, 'exception', null) !== null) {
						flashMessage.exception(e.exception, errorMessage);
					} else {
						flashMessage.error(errorMessage);
					}

					emit('update:remoteFormResult', FbFormResultTypes.ERROR);

					timer = window.setTimeout(clearResult, 2000);
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
			setCurrentPassword('');
			setNewPassword('');
			setRepeatPassword('');
		}
	}
);
</script>

<i18n src="../../locales/locales.json" />
