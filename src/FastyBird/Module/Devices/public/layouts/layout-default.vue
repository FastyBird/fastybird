<template>
	<RouterView @toggle-menu="emit('toggleMenu', $event)" />
</template>

<script setup lang="ts">
import { computed, onBeforeMount, onMounted, onUnmounted } from 'vue';
import get from 'lodash/get';

import { useWsExchangeClient } from '@fastybird/ws-exchange-plugin';

import {
	useChannelControls,
	useChannelProperties,
	useChannels,
	useConnectorControls,
	useConnectorProperties,
	useConnectors,
	useDeviceControls,
	useDeviceProperties,
	useDevices,
} from '@/models';
import { ApplicationError } from '@/errors';

const emit = defineEmits<{
	(e: 'toggleMenu', event: Event): void;
}>();

const wampV1Client = useWsExchangeClient();

const connectorsStore = useConnectors();

const isLoading = computed<boolean>((): boolean => connectorsStore.fetching);

const stores = computed(() => {
	return [
		useChannels(),
		useChannelControls(),
		useChannelProperties(),
		useConnectors(),
		useConnectorControls(),
		useConnectorProperties(),
		useDevices(),
		useDeviceControls(),
		useDeviceProperties(),
	];
});

const onWsMessage = (data: string): void => {
	const body = JSON.parse(data);

	if (
		Object.prototype.hasOwnProperty.call(body, 'routing_key') &&
		Object.prototype.hasOwnProperty.call(body, 'source') &&
		Object.prototype.hasOwnProperty.call(body, 'data')
	) {
		stores.value.forEach((store) => {
			if (Object.prototype.hasOwnProperty.call(store, 'socketData')) {
				store.socketData({
					source: get(body, 'source'),
					routingKey: get(body, 'routing_key'),
					data: JSON.stringify(get(body, 'data')),
				});
			}
		});
	}
};

onBeforeMount(async (): Promise<void> => {
	if (!isLoading.value && !connectorsStore.firstLoadFinished) {
		try {
			await connectorsStore.fetch({ withDevices: true });
		} catch (e: any) {
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		}
	}
});

onMounted((): void => {
	wampV1Client.client.subscribe('/io/exchange', onWsMessage);
});

onUnmounted((): void => {
	wampV1Client.client.unsubscribe('/io/exchange', onWsMessage);
});
</script>
