<template>
	<RouterView @toggle-menu="emit('toggleMenu', $event)" />
</template>

<script setup lang="ts">
import { computed, onBeforeMount } from 'vue';

import { useConnectors } from '@/models';
import { ApplicationError } from '@/errors';

const emit = defineEmits<{
	(e: 'toggleMenu', event: Event): void;
}>();

const connectorsStore = useConnectors();

const isLoading = computed<boolean>((): boolean => connectorsStore.fetching);

onBeforeMount(async (): Promise<void> => {
	if (!isLoading.value && !connectorsStore.firstLoadFinished) {
		try {
			await connectorsStore.fetch({ withDevices: true });
		} catch (e: any) {
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		}
	}
});
</script>
