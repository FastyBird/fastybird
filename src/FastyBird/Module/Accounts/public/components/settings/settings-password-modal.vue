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
			<font-awesome-icon icon="key" />
		</template>

		<template #title>
			{{ t('headings.passwordChange') }}
		</template>

		<template #form>
			<settings-password-form
				v-if="identity"
				v-model:remote-form-submit="remoteFormSubmit"
				v-model:remote-form-result="remoteFormResult"
				:identity="identity"
			/>
		</template>
	</fb-ui-modal-form>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FbUiModalForm, FbFormResultTypes, FbUiModalLayoutTypes } from '@fastybird/web-ui-library';

import { useBreakpoints } from '@/composables';
import { useIdentities, useSession } from '@/models';
import { IIdentity } from '@/models/identities/types';

const emit = defineEmits<{
	(e: 'close'): void;
}>();

const { t } = useI18n();
const { isExtraSmallDevice } = useBreakpoints();

const sessionStore = useSession();
const identitiesStore = useIdentities();

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FbFormResultTypes>(FbFormResultTypes.NONE);

const isMounted = ref<boolean>(false);
const identity = computed<IIdentity | null>((): IIdentity | null => {
	if (sessionStore.account === null || sessionStore.account.email === null) {
		return null;
	}

	const identity = identitiesStore.findForAccount(sessionStore.account.id).find((identity) => identity.uid === sessionStore.account?.email?.address);

	return identity ?? null;
});

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

<i18n src="@/locales/locales.json" />
