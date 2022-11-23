<template>
	<layout-sign-box>
		<layout-sign-header :heading="t('headings.signIn')" />

		<form @submit.prevent="onSubmit">
			<sign-in-form
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
				{{ t('buttons.signIn.title') }}
			</fb-ui-button>

			<fb-ui-content
				:mt="FbSizeTypes.MEDIUM"
				class="fb-accounts-module-view-sign-in__reset-password"
			>
				<fb-ui-button
					:variant="FbUiButtonVariantTypes.LINK"
					:action-type="FbUiButtonActionsTypes.VUE_LINK"
					:action="{ name: routeNames.resetPassword }"
				>
					{{ t('buttons.forgotPassword.title') }}
				</fb-ui-button>
			</fb-ui-content>
		</form>
	</layout-sign-box>
</template>

<script setup lang="ts">
import { inject, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Emitter } from 'mitt';

import {
	FbFormResultTypes,
	FbSizeTypes,
	FbUiButton,
	FbUiButtonActionsTypes,
	FbUiButtonVariantTypes,
	FbUiButtonButtonTypes,
	FbUiContent,
} from '@fastybird/web-ui-library';

import { configurationKey } from '@/configuration';
import { EventBusEventsType } from '@/types';
import { useRoutesNames } from '@/composables';
import { LayoutSignBox, LayoutSignHeader, SignInForm } from '@/components';

const configuration = inject(configurationKey);

const { t } = useI18n();
const { routeNames } = useRoutesNames();

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

			if (state === FbFormResultTypes.OK) {
				eventsBus?.emit('userSigned', 'in');
			}
		}
	}
);
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'view-sign-in';
</style>

<i18n src="@/locales/locales.json" />
