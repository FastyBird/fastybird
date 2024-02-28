<template>
	<layout-sign-box>
		<layout-sign-header :heading="t('headings.signUp')" />

		<form @submit.prevent="onSubmit">
			<sign-up-form
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
				{{ t('buttons.signUp.title') }}
			</fb-ui-button>
		</form>
	</layout-sign-box>
</template>

<script setup lang="ts">
import { inject, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Emitter } from 'mitt';

import { FbFormResultTypes, FbUiButton, FbUiButtonVariantTypes, FbUiButtonButtonTypes } from '@fastybird/web-ui-library';

import { configurationKey } from '../configuration';
import { EventBusEventsType } from '../types';
import { LayoutSignHeader, LayoutSignBox, SignUpForm } from '../components';

const configuration = inject(configurationKey);

const { t } = useI18n();

const eventsBus = inject<Emitter<EventBusEventsType>>(configuration?.injectionKeys.eventBusInjectionKey ?? 'eventBus');

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FbFormResultTypes>(FbFormResultTypes.NONE);

// Submit form
const onSubmit = (): void => {
	remoteFormSubmit.value = true;
};

watch(
	(): FbFormResultTypes => remoteFormResult.value,
	(state: FbFormResultTypes): void => {
		if (state === FbFormResultTypes.WORKING) {
			eventsBus?.emit('loadingOverlay', 10);
		} else {
			eventsBus?.emit('loadingOverlay', false);
		}
	}
);
</script>

<i18n src="../locales/locales.json" />
