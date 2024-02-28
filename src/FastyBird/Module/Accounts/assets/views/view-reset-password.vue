<template>
	<layout-sign-box>
		<layout-sign-header
			v-if="isSubmitted"
			:heading="t('headings.instructionEmailed')"
		/>
		<layout-sign-header
			v-else
			:heading="t('headings.passwordReset')"
		/>

		<div
			v-if="makingRequest"
			class="fb-accounts-module-view-reset-password__processing"
		>
			<fb-ui-spinner :size="FbSizeTypes.LARGE" />

			<strong>{{ t('texts.processing') }}</strong>
		</div>

		<div v-if="!makingRequest">
			<p
				v-if="isSubmitted"
				class="fb-accounts-module-view-reset-password__info"
			>
				<small>{{ t('texts.resetPasswordInstructionsEmailed') }}</small>
			</p>

			<form
				v-if="!isSubmitted"
				@submit.prevent="onSubmit"
			>
				<reset-password-form
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-result="remoteFormResult"
				/>

				<fb-ui-button
					:variant="FbUiButtonVariantTypes.PRIMARY"
					:type="FbUiButtonButtonTypes.SUBMIT"
					block
					uppercase
					@click="onSubmit"
				>
					{{ t('buttons.resetPassword.title') }}
				</fb-ui-button>

				<p class="fb-accounts-module-view-reset-password__info">
					<small>{{ t('texts.resetPasswordInfo') }}</small>
				</p>
			</form>
		</div>
	</layout-sign-box>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { FbUiButton, FbUiSpinner, FbSizeTypes, FbUiButtonVariantTypes, FbUiButtonButtonTypes, FbFormResultTypes } from '@fastybird/web-ui-library';

import { LayoutSignHeader, LayoutSignBox, ResetPasswordForm } from '../components';

const { t } = useI18n();

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FbFormResultTypes>(FbFormResultTypes.NONE);

const makingRequest = ref<boolean>(false);
const isSubmitted = ref<boolean>(false);

// Submit form
const onSubmit = (): void => {
	remoteFormSubmit.value = true;
};

watch(
	(): FbFormResultTypes => remoteFormResult.value,
	(state: FbFormResultTypes): void => {
		if (state === FbFormResultTypes.WORKING) {
			makingRequest.value = true;
			isSubmitted.value = false;
		} else if (state === FbFormResultTypes.OK) {
			isSubmitted.value = true;
			makingRequest.value = false;
		} else if (state === FbFormResultTypes.ERROR) {
			makingRequest.value = false;
		}
	}
);
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'view-reset-password';
</style>

<i18n src="../locales/locales.json" />
