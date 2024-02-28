<template>
	<fb-ui-modal-form
		:transparent-bg="false"
		:lock-submit-button="remoteFormResult !== FbFormResultTypes.NONE"
		:state="remoteFormResult"
		:layout="isExtraSmallDevice ? FbUiModalLayoutTypes.PHONE : FbUiModalLayoutTypes.DEFAULT"
		@submit="onSubmitForm"
		@cancel="onClose"
		@close="onClose"
	>
		<template #icon>
			<font-awesome-icon icon="user" />
		</template>

		<template #title>
			{{ t('headings.accountSettings') }}
		</template>

		<template #form>
			<settings-account-form
				v-if="sessionStore.account"
				v-model:remote-form-submit="remoteFormSubmit"
				v-model:remote-form-result="remoteFormResult"
				:account="sessionStore.account"
			/>
		</template>
	</fb-ui-modal-form>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FbUiModalForm, FbFormResultTypes, FbUiModalLayoutTypes } from '@fastybird/web-ui-library';

import { useBreakpoints } from '../../composables';
import { useSession } from '../../models';
import SettingsAccountForm from './settings-account-form.vue';

const emit = defineEmits<{
	(e: 'close'): void;
}>();

const { t } = useI18n();
const { isExtraSmallDevice } = useBreakpoints();

const sessionStore = useSession();

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FbFormResultTypes>(FbFormResultTypes.NONE);

const isMounted = ref<boolean>(false);

const onClose = (): void => {
	emit('close');
};

const onSubmitForm = (): void => {
	remoteFormSubmit.value = true;
};

onMounted((): void => {
	isMounted.value = true;
});

watch(
	(): FbFormResultTypes => remoteFormResult.value,
	(actual, previous): void => {
		if (actual === FbFormResultTypes.NONE && previous === FbFormResultTypes.OK) {
			emit('close');
		}
	}
);
</script>

<i18n src="../../locales/locales.json" />
