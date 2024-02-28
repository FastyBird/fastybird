<template>
	<fb-ui-content
		:mb="FbSizeTypes.MEDIUM"
		class="fb-accounts-module-sign-up-form__name"
	>
		<fb-form-input
			v-model="firstName"
			:error="firstNameError"
			:label="t('fields.firstName.title')"
			:required="true"
			name="firstName"
		>
			<template #help-line>
				{{ t('fields.firstName.help') }}
			</template>
		</fb-form-input>

		<fb-form-input
			v-model="lastName"
			:error="lastNameError"
			:label="t('fields.lastName.title')"
			:required="true"
			name="lastName"
		>
			<template #help-line>
				{{ t('fields.lastName.help') }}
			</template>
		</fb-form-input>
	</fb-ui-content>

	<fb-ui-content :mb="FbSizeTypes.MEDIUM">
		<fb-form-input
			v-model="emailAddress"
			:error="emailAddressError"
			:label="t('fields.emailAddress.title')"
			:required="true"
			name="emailAddress"
		>
			<template #help-line>
				{{ t('fields.emailAddress.help') }}
			</template>
		</fb-form-input>
	</fb-ui-content>

	<fb-ui-content :mb="FbSizeTypes.MEDIUM">
		<fb-form-input
			v-model="password"
			:error="passwordError"
			:label="t('fields.password.new.title')"
			:required="true"
			:type="FbFormInputTypeTypes.PASSWORD"
			name="password"
		>
			<template #help-line>
				{{ t('fields.password.new.help') }}
			</template>
		</fb-form-input>
	</fb-ui-content>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useForm, useField } from 'vee-validate';
import { object as yObject, string as yString } from 'yup';
import get from 'lodash/get';

import { FbUiContent, FbFormInput, FbFormInputTypeTypes, FbSizeTypes, FbFormResultTypes } from '@fastybird/web-ui-library';

import { useFlashMessage } from '../../composables';
import { ISignUpForm, ISignUpProps } from './sign-up-form.types';

const props = withDefaults(defineProps<ISignUpProps>(), {
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

const { validate } = useForm<ISignUpForm>({
	validationSchema: yObject({
		emailAddress: yString().required(t('fields.emailAddress.validation.required')).email(t('fields.emailAddress.validation.email')),
		firstName: yString().required(t('fields.firstName.validation.required')),
		lastName: yString().required(t('fields.lastName.validation.required')),
		password: yString().required(t('fields.password.new.validation.required')),
	}),
});

const { value: emailAddress, errorMessage: emailAddressError, setValue: setEmailAddress } = useField<string>('emailAddress');
const { value: firstName, errorMessage: firstNameError, setValue: setFirstName } = useField<string>('firstName');
const { value: lastName, errorMessage: lastNameError, setValue: setLastName } = useField<string>('lastName');
const { value: password, errorMessage: passwordError, setValue: setPassword } = useField<string>('password');

watch(
	(): boolean => props.remoteFormSubmit,
	async (val): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			const validationResult = await validate();

			if (validationResult.valid) {
				emit('update:remoteFormResult', FbFormResultTypes.WORKING);

				try {
					// TODO: Implement registration

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
			setEmailAddress('');
			setFirstName('');
			setLastName('');
			setPassword('');
		}
	}
);
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'sign-up-form';
</style>

<i18n src="../../locales/locales.json" />
