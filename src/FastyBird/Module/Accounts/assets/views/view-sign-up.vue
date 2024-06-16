<template>
	<layout-sign-box>
		<layout-sign-header :heading="t('headings.signUp')" />

		<sign-up-form v-model:remote-form-result="remoteFormResult" />
	</layout-sign-box>
</template>

<script setup lang="ts">
import { inject, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { Emitter } from 'mitt';

import { configurationKey } from '../configuration';
import { EventBusEventsType, FormResultTypes } from '../types';
import { LayoutSignHeader, LayoutSignBox, SignUpForm } from '../components';

defineOptions({
	name: 'ViewSignUp',
});

const configuration = inject(configurationKey);

const { t } = useI18n();

const eventsBus = inject<Emitter<EventBusEventsType>>(configuration?.injectionKeys.eventBusInjectionKey ?? 'eventBus');

const remoteFormResult = ref<FormResultTypes>(FormResultTypes.NONE);

watch(
	(): FormResultTypes => remoteFormResult.value,
	(state: FormResultTypes): void => {
		if (state === FormResultTypes.WORKING) {
			eventsBus?.emit('loadingOverlay', 10);
		} else {
			eventsBus?.emit('loadingOverlay', false);
		}
	}
);

useMeta({
	title: t('meta.sign.up.title'),
});
</script>
