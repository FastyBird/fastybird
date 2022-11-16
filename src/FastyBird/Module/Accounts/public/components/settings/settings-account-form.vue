<template>
	<fb-ui-content
		:mb="FbSizeTypes.LARGE"
		class="fb-accounts-module-settings-account-form__columns"
	>
		<div class="fb-accounts-module-settings-account-form__column">
			<fb-form-input
				v-model="emailAddress"
				:error="emailAddressError"
				:label="t('fields.emailAddress.title')"
				:required="true"
				:type="FbFormInputTypeTypes.EMAIL"
				name="emailAddress"
				spellcheck="false"
			>
				<template #help-line>
					{{ t('fields.emailAddress.help') }}
				</template>
			</fb-form-input>
		</div>

		<div class="fb-accounts-module-settings-account-form__column">
			<fb-form-select
				v-model="language"
				:label="t('fields.language.title')"
				:items="languagesOptions"
				name="language"
			/>
		</div>
	</fb-ui-content>

	<fb-ui-content
		:mb="FbSizeTypes.LARGE"
		class="fb-accounts-module-settings-account-form__columns"
	>
		<div class="fb-accounts-module-settings-account-form__name-column">
			<fb-form-input
				v-model="firstName"
				:error="firstNameError"
				:label="t('fields.firstName.title')"
				:required="true"
				name="firstName"
				spellcheck="false"
			>
				<template #help-line>
					{{ t('fields.firstName.help') }}
				</template>
			</fb-form-input>
		</div>

		<div class="fb-accounts-module-settings-account-form__name-column">
			<fb-form-input
				v-model="lastName"
				:error="lastNameError"
				:label="t('fields.lastName.title')"
				:required="true"
				name="lastName"
				spellcheck="false"
			>
				<template #help-line>
					{{ t('fields.lastName.help') }}
				</template>
			</fb-form-input>
		</div>

		<div class="fb-accounts-module-settings-account-form__name-column">
			<fb-form-input
				v-model="middleName"
				:label="t('fields.middleName.title')"
				:required="true"
				name="middleName"
				spellcheck="false"
			/>
		</div>
	</fb-ui-content>

	<hr />

	<fb-ui-content
		:mb="FbSizeTypes.LARGE"
		class="fb-accounts-module-settings-account-form__columns"
	>
		<div class="fb-accounts-module-settings-account-form__column">
			<fb-form-select
				v-model="timezone"
				:label="t('fields.datetime.timezone.title')"
				:items="zonesOptions"
				name="zone"
			/>
		</div>

		<div class="fb-accounts-module-settings-account-form__column">
			<fb-form-select
				v-model="weekStart"
				:label="t('fields.datetime.weekStartOn.title')"
				:items="weekStartOptions"
				name="weekStart"
			/>
		</div>
	</fb-ui-content>

	<fb-ui-content
		:mb="FbSizeTypes.LARGE"
		class="fb-accounts-module-settings-account-form__columns"
	>
		<div class="fb-accounts-module-settings-account-form__column">
			<fb-form-select
				v-model="dateFormat"
				:label="t('fields.datetime.dateFormat.title')"
				:items="dateFormatOptions"
				name="dateFormat"
			/>
		</div>

		<div class="fb-accounts-module-settings-account-form__column">
			<fb-form-select
				v-model="timeFormat"
				:label="t('fields.datetime.timeFormat.title')"
				:items="timeFormatOptions"
				name="timeFormat"
			/>
		</div>
	</fb-ui-content>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { useForm, useField } from 'vee-validate';
import * as yup from 'yup';
import { useI18n } from 'vue-i18n';
import get from 'lodash/get';

import {
	FbFormInput,
	FbFormSelect,
	FbUiContent,
	FbSizeTypes,
	FbFormInputTypeTypes,
	FbFormResultTypes,
	IFbFormSelectItem,
	IFbFormSelectItemGroup,
} from '@fastybird/web-ui-library';

import { useAccount } from '@/models';
import { useFlashMessage, useTimezones } from '@/composables';
import { ISettingsAccountProps } from '@/components/settings/settings-account-form.types';

const props = withDefaults(defineProps<ISettingsAccountProps>(), {
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
const { timezones } = useTimezones();

const countries: string[] = ['Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific'];

const languagesOptions: IFbFormSelectItem[] = [
	{
		name: 'English',
		value: 'en',
	},
	{
		name: 'Czech',
		value: 'cs',
	},
];

const weekStartOptions: IFbFormSelectItem[] = [
	{
		name: t('fields.datetime.weekStartOn.values.monday'),
		value: 1,
	},
	{
		name: t('fields.datetime.weekStartOn.values.saturday'),
		value: 6,
	},
	{
		name: t('fields.datetime.weekStartOn.values.sunday'),
		value: 7,
	},
];

const dateFormatOptions: IFbFormSelectItem[] = [
	{
		name: 'mm/dd/yyyy',
		value: 'MM/dd/yyyy',
	},
	{
		name: 'dd/mm/yyyy',
		value: 'dd/MM/yyyy',
	},
	{
		name: 'dd.mm.yyyy',
		value: 'dd.MM.yyyy',
	},
	{
		name: 'yyyy-mm-dd',
		value: 'yyyy-MM-dd',
	},
];

const timeFormatOptions: IFbFormSelectItem[] = [
	{
		name: 'hh:mm',
		value: 'HH:mm',
	},
	{
		name: 'hh:mm am/pm',
		value: 'hh:mm a',
	},
];

const zonesOptions = countries.map((country): IFbFormSelectItemGroup => {
	return {
		name: country,
		items: timezones
			.filter((zone) => zone.substring(0, zone.search('/')) === country)
			.map((timezone) => {
				return {
					value: timezone,
					name: timezone.substring(timezone.search('/') + 1),
				};
			})
			.sort((a, b) => {
				const nameA = a.name.toUpperCase();
				const nameB = b.name.toUpperCase();

				if (nameA < nameB) {
					return -1;
				}

				if (nameA > nameB) {
					return 1;
				}

				return 0;
			}),
	};
});

interface ISettingsAccountForm {
	emailAddress: string;
	firstName: string;
	lastName: string;
	middleName?: string;
	language: string;
	weekStart: number;
	timezone: string;
	dateFormat: string;
	timeFormat: string;
}

const { validate } = useForm<ISettingsAccountForm>({
	validationSchema: yup.object({
		emailAddress: yup.string().required(t('fields.emailAddress.validation.required')).email(t('fields.emailAddress.validation.email')),
		firstName: yup.string().required(t('fields.firstName.validation.required')),
		lastName: yup.string().required(t('fields.lastName.validation.required')),
		middleName: yup.string().notRequired(),
		language: yup.string().required(),
		weekStart: yup.number().required(),
		timezone: yup.string().required(),
		dateFormat: yup.string().required(),
		timeFormat: yup.string().required(),
	}),
	initialValues: {
		emailAddress: props.account.email?.address ?? '@',
		firstName: props.account.details.firstName,
		lastName: props.account.details.lastName,
		middleName: props.account.details.middleName ?? undefined,
		language: props.account.language,
		weekStart: props.account.weekStart,
		timezone: props.account.dateTime.timezone,
		dateFormat: props.account.dateTime.dateFormat,
		timeFormat: props.account.dateTime.timeFormat,
	},
});

const { value: emailAddress, errorMessage: emailAddressError, setValue: setEmailAddress } = useField<string>('emailAddress');
const { value: firstName, errorMessage: firstNameError, setValue: setFirstName } = useField<string>('firstName');
const { value: lastName, errorMessage: lastNameError, setValue: setLastName } = useField<string>('lastName');
const { value: middleName, setValue: setMiddleName } = useField<string | undefined>('middleName');
const { value: language, setValue: setLanguage } = useField<string>('language');
const { value: weekStart, setValue: setWeekStart } = useField<number>('weekStart');
const { value: timezone, setValue: setTimezone } = useField<string>('timezone');
const { value: dateFormat, setValue: setDateFormat } = useField<string>('dateFormat');
const { value: timeFormat, setValue: setTimeFormat } = useField<string>('timeFormat');

let timer: number;

const clearResult = (): void => {
	window.clearTimeout(timer);

	emit('update:remoteFormResult', FbFormResultTypes.NONE);
};

const updateAccount = async (): Promise<void> => {
	const errorMessage = t('messages.accountNotEdited');

	try {
		await accountStore.edit({
			data: {
				details: {
					firstName: firstName.value,
					lastName: lastName.value,
					middleName: middleName.value,
				},
				language: language.value,
				weekStart: weekStart.value,
				dateTime: {
					timezone: timezone.value,
					dateFormat: dateFormat.value,
					timeFormat: timeFormat.value,
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
};

watch(
	(): boolean => props.remoteFormSubmit,
	async (val): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			const validationResult = await validate();

			if (validationResult.valid) {
				emit('update:remoteFormResult', FbFormResultTypes.WORKING);

				// Email has been changed
				if (emailAddress.value !== props.account.email?.address) {
					const storedEmail = accountStore.emails.find((email) => email.address.toLowerCase() === emailAddress.value.toLowerCase());

					const emailErrorMessage = t('messages.emailNotEdited');

					if (storedEmail !== undefined) {
						try {
							await accountStore.editEmail({
								id: storedEmail.id,
								data: {
									default: true,
									private: storedEmail.private,
								},
							});

							await updateAccount();
						} catch (e: any) {
							if (get(e, 'exception', null) !== null) {
								flashMessage.exception(e.exception, emailErrorMessage);
							} else {
								flashMessage.error(emailErrorMessage);
							}

							emit('update:remoteFormResult', FbFormResultTypes.ERROR);

							timer = window.setTimeout(clearResult, 2000);
						}
					} else {
						try {
							await accountStore.addEmail({
								data: {
									address: emailAddress.value,
									default: true,
									private: false,
								},
							});

							await updateAccount();
						} catch (e: any) {
							if (get(e, 'exception', null) !== null) {
								flashMessage.exception(e.exception, emailErrorMessage);
							} else {
								flashMessage.error(emailErrorMessage);
							}

							emit('update:remoteFormResult', FbFormResultTypes.ERROR);

							timer = window.setTimeout(clearResult, 2000);
						}
					}
				} else {
					await updateAccount();
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
			setEmailAddress(props.account.email?.address ?? '@');
			setFirstName(props.account.details.firstName);
			setLastName(props.account.details.lastName);
			setMiddleName(props.account.details.middleName ?? undefined);
			setLanguage(props.account.language);
			setWeekStart(props.account.weekStart);
			setTimezone(props.account.dateTime.timezone);
			setDateFormat(props.account.dateTime.dateFormat);
			setTimeFormat(props.account.dateTime.timeFormat);
		}
	}
);
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'settings-account-form';
</style>

<i18n src="@/locales/locales.json" />
