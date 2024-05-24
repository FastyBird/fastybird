<template>
	<el-card v-if="!isXsBreakpoint">
		<settings-password-form
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			v-model:remote-form-reset="remoteFormReset"
			:identity="identity!"
		/>
	</el-card>

	<template v-else>
		<settings-password-form
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			v-model:remote-form-reset="remoteFormReset"
			:identity="identity!"
			:layout="LayoutTypes.PHONE"
		/>

		<el-button
			:loading="remoteFormResult === FormResultTypes.WORKING"
			:disabled="remoteFormResult === FormResultTypes.WORKING"
			type="primary"
			class="w-full mt-5"
			@click="onSave"
		>
			{{ t('buttons.save.title') }}
		</el-button>
	</template>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { ElButton, ElCard } from 'element-plus';

import { breakpointsBootstrapV5, useBreakpoints } from '@vueuse/core';

import { SettingsPasswordForm } from '../components';
import { useIdentities, useSession } from '../models';
import { FormResultTypes, IIdentity, LayoutTypes } from '../types';
import { IViewPasswordProps } from './view-password.types';

defineOptions({
	name: 'ViewPassword',
});

const props = withDefaults(defineProps<IViewPasswordProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FormResultTypes): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
}>();

const { t } = useI18n();

const sessionStore = useSession();
const identitiesStore = useIdentities();
const breakpoints = useBreakpoints(breakpointsBootstrapV5);

const isXsBreakpoint = breakpoints.smaller('sm');
const remoteFormSubmit = ref<boolean>(props.remoteFormSubmit);
const remoteFormResult = ref<FormResultTypes>(props.remoteFormResult);
const remoteFormReset = ref<boolean>(props.remoteFormReset);

const identity = computed<IIdentity | null>((): IIdentity | null => {
	if (sessionStore.account === null || sessionStore.account.email === null) {
		return null;
	}

	const identity = identitiesStore.findForAccount(sessionStore.account.id).find((identity) => identity.uid === sessionStore.account?.email?.address);

	return identity ?? null;
});

const onSave = (): void => {
	remoteFormSubmit.value = true;
};

watch(
	(): boolean => props.remoteFormSubmit,
	async (val: boolean): Promise<void> => {
		remoteFormSubmit.value = val;
	}
);

watch(
	(): boolean => props.remoteFormReset,
	async (val: boolean): Promise<void> => {
		remoteFormReset.value = val;
	}
);

watch(
	(): boolean => remoteFormSubmit.value,
	async (val: boolean): Promise<void> => {
		emit('update:remoteFormSubmit', val);
	}
);

watch(
	(): FormResultTypes => remoteFormResult.value,
	async (val: FormResultTypes): Promise<void> => {
		emit('update:remoteFormResult', val);
	}
);

watch(
	(): boolean => remoteFormReset.value,
	async (val: boolean): Promise<void> => {
		emit('update:remoteFormReset', val);
	}
);

useMeta({
	title: t('meta.profile.password.title'),
});
</script>

<i18n src="../locales/locales.json" />
