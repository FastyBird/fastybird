<template>
	<el-dialog
		ref="dialogRef"
		v-model="dialogVisible"
		:show-close="false"
		:fullscreen="isExtraSmallDevice"
		:draggable="!isExtraSmallDevice"
		class="p-0"
		@close="onClose"
	>
		<template #header>
			<fb-dialog-header
				:layout="isExtraSmallDevice ? 'phone' : 'default'"
				:left-btn-label="t('buttons.close.title')"
				:right-btn-label="t('buttons.save.title')"
				@close="onClose"
			>
				<template #title>
					{{ t('headings.passwordChange') }}
				</template>

				<template #icon>
					<fas-key />
				</template>
			</fb-dialog-header>
		</template>

		<settings-password-form
			v-if="identity"
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			:identity="identity"
		/>

		<template #footer>
			<fb-dialog-footer
				:layout="isExtraSmallDevice ? 'phone' : 'default'"
				:left-btn-label="t('buttons.close.title')"
				:right-btn-label="t('buttons.save.title')"
				@left-click="onClose"
				@right-click="onSave"
			/>
		</template>
	</el-dialog>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElDialog, ElLoading } from 'element-plus';

import { FbDialogHeader, FbDialogFooter } from '@fastybird/web-ui-library';
import { FasKey } from '@fastybird/web-ui-icons';

import { useBreakpoints } from '../../composables';
import { useIdentities, useSession } from '../../models';
import { FormResultTypes, IIdentity } from '../../types';
import { ISettingsPasswordDialogProps } from './settings-password-dialog.types';
import SettingsPasswordForm from './settings-password-form.vue';

defineOptions({
	name: 'SettingsPasswordDialog',
});

const props = defineProps<ISettingsPasswordDialogProps>();

const emit = defineEmits<{
	(e: 'update:visible', visibility: boolean): void;
	(e: 'close'): void;
}>();

const { t } = useI18n();
const { isExtraSmallDevice } = useBreakpoints();

const sessionStore = useSession();
const identitiesStore = useIdentities();

const dialogRef = ref();

const dialogVisible = ref<boolean>(props.visible);
const loading = ref<any | undefined>(undefined);

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FormResultTypes>(FormResultTypes.NONE);

const isMounted = ref<boolean>(false);

const identity = computed<IIdentity | null>((): IIdentity | null => {
	if (sessionStore.account === null || sessionStore.account.email === null) {
		return null;
	}

	const identity = identitiesStore.findForAccount(sessionStore.account.id).find((identity) => identity.uid === sessionStore.account?.email?.address);

	return identity ?? null;
});

const onClose = (): void => {
	emit('update:visible', false);
	emit('close');
};

const onSave = (): void => {
	remoteFormSubmit.value = true;
};

onMounted((): void => {
	isMounted.value = true;
});

watch(
	(): FormResultTypes => remoteFormResult.value,
	(actual, previous): void => {
		if (actual === FormResultTypes.WORKING) {
			loading.value = ElLoading.service({
				target: dialogRef.value.$el.nextElementSibling.querySelector('.el-dialog'),
			});
		} else {
			(loading.value as any)?.close();
		}

		if (actual === FormResultTypes.NONE && previous === FormResultTypes.OK) {
			emit('close');
		}
	}
);
</script>
