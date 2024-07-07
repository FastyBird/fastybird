<template>
	<el-card v-if="!isXsBreakpoint">
		<settings-account-form
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			v-model:remote-form-reset="remoteFormReset"
			:account="sessionStore.account()!"
		/>
	</el-card>

	<template v-else>
		<settings-account-form
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			v-model:remote-form-reset="remoteFormReset"
			:account="sessionStore.account()!"
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
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { ElButton, ElCard } from 'element-plus';

import { breakpointsBootstrapV5, useBreakpoints } from '@vueuse/core';

import { SettingsAccountForm } from '../components';
import { useSession } from '../models';
import { FormResultTypes, LayoutTypes } from '../types';
import { IViewProfileProps } from './view-profile.types';

defineOptions({
	name: 'ViewProfile',
});

const props = withDefaults(defineProps<IViewProfileProps>(), {
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
const breakpoints = useBreakpoints(breakpointsBootstrapV5);

const isXsBreakpoint = breakpoints.smaller('sm');
const remoteFormSubmit = ref<boolean>(props.remoteFormSubmit);
const remoteFormResult = ref<FormResultTypes>(props.remoteFormResult);
const remoteFormReset = ref<boolean>(props.remoteFormReset);

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
	title: t('meta.profile.profile.title'),
});
</script>
