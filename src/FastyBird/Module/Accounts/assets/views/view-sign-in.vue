<template>
	<layout-sign-box>
		<layout-sign-header :heading="t('headings.signIn')" />

		<sign-in-form v-model:remote-form-result="remoteFormResult" />

		<div class="mt-5 text-center">
			<el-button
				link
				@click="router.push({ name: routeNames.resetPassword })"
			>
				{{ t('buttons.forgotPassword.title') }}
			</el-button>
		</div>
	</layout-sign-box>
</template>

<script setup lang="ts">
import { inject, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { Emitter } from 'mitt';
import { ElButton } from 'element-plus';

import { configurationKey } from '../configuration';
import { EventBusEventsType, FormResultTypes } from '../types';
import { useRoutesNames } from '../composables';
import { LayoutSignBox, LayoutSignHeader, SignInForm } from '../components';

defineOptions({
	name: 'ViewSignIn',
});

const configuration = inject(configurationKey);

const { t } = useI18n();
const { routeNames } = useRoutesNames();
const router = useRouter();

const eventsBus = inject<Emitter<EventBusEventsType>>(configuration?.injectionKeys.eventBusInjectionKey ?? 'eventBus');

const remoteFormResult = ref<FormResultTypes>(FormResultTypes.NONE);

watch(
	(): FormResultTypes => remoteFormResult.value,
	(state: FormResultTypes): void => {
		if (state === FormResultTypes.WORKING) {
			eventsBus?.emit('loadingOverlay', 10);
		} else {
			eventsBus?.emit('loadingOverlay', false);

			if (state === FormResultTypes.OK) {
				eventsBus?.emit('userSigned', 'in');
			}
		}
	}
);

useMeta({
	title: t('meta.sign.in.title'),
});
</script>

<i18n src="../locales/locales.json" />
