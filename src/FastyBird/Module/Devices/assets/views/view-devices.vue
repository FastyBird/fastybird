<template>
	<fb-app-bar-heading
		v-if="isXSDevice && !isPartialDetailRoute"
		teleport
	>
		<template #icon>
			<fas-plug />
		</template>

		<template #title>
			{{ t('headings.devices.allDevices') }}
		</template>

		<template #subtitle>
			{{ t('subHeadings.devices.allDevices', { count: items.length }, items.length) }}
		</template>
	</fb-app-bar-heading>

	<fb-app-bar-button
		v-if="isXSDevice && !isPartialDetailRoute"
		teleport
		:align="AppBarButtonAlignTypes.LEFT"
		small
		@click="onOpenRegister"
	>
		<span class="uppercase">{{ t('buttons.new.title') }}</span>
	</fb-app-bar-button>

	<div class="sm:flex sm:flex-row h-full w-full">
		<view-error
			v-if="isXSDevice && isPartialDetailRoute"
			:type="'connector'"
		>
			<router-view />
		</view-error>

		<div
			v-if="!isPartialDetailRoute || !isXSDevice"
			class="sm:w-[20rem] sm:border-r sm:border-r-solid"
		>
			<devices-list-devices
				v-loading="isLoading"
				:element-loading-text="t('texts.misc.loadingDevices')"
				:items="items"
				@open="onOpenDetail"
				@remove="onRemove"
			/>
		</div>

		<template v-if="!isXSDevice">
			<view-error
				v-if="isPartialDetailRoute"
				:type="'connector'"
			>
				<router-view class="flex-grow h-full" />
			</view-error>

			<devices-preview-info
				v-else
				:total="itemsCount"
				class="h-full"
				@register="onOpenRegister"
				@synchronise="onSynchronise"
			/>
		</template>
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRoute, useRouter } from 'vue-router';
import get from 'lodash.get';
import { orderBy } from 'natural-orderby';
import { ElMessageBox, vLoading } from 'element-plus';

import { FasPlug } from '@fastybird/web-ui-icons';
import { AppBarButtonAlignTypes, FbAppBarButton, FbAppBarHeading } from '@fastybird/web-ui-library';

import { useBreakpoints, useEntityTitle, useFlashMessage, useRoutesNames } from '../composables';
import { useDevices } from '../models';
import { IDevice } from '../models/types';
import { DevicesPreviewInfo, DevicesListDevices, ViewError } from '../components';
import { ApplicationError } from '../errors';

defineOptions({
	name: 'ViewDevices',
});

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const { isXSDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const flashMessage = useFlashMessage();

const devicesStore = useDevices();

const itemsSearch = ref<string>('');

const itemsCount = computed<number>((): number => Object.keys(devicesStore.data).length);

const isLoading = computed<boolean>((): boolean => devicesStore.fetching(null));

const isPartialDetailRoute = computed<boolean>((): boolean => {
	return route.matched.find((matched) => matched.name === routeNames.deviceDetail) !== undefined;
});

const items = computed<IDevice[]>((): IDevice[] => {
	return orderBy<IDevice>(
		Object.values(devicesStore.data)
			.filter((device) => !device.draft)
			.filter((device) => {
				return itemsSearch.value === '' || useEntityTitle(device).value.toLowerCase().includes(itemsSearch.value.toLowerCase());
			}),
		[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
		['asc']
	);
});

const onOpenDetail = (id: string): void => {
	router.push({
		name: routeNames.deviceDetail,
		params: {
			id,
		},
	});
};

const onOpenRegister = (): void => {
	router.push({
		name: routeNames.deviceConnect,
	});
};

const onSynchronise = (): void => {
	// TODO: Handle refresh
};

const onRemove = async (id: string): Promise<void> => {
	const device = await devicesStore.findById(id);

	if (device === null) {
		return;
	}

	ElMessageBox.confirm(t('messages.devices.confirmRemove', { device: useEntityTitle(device).value }), t('headings.devices.remove'), {
		confirmButtonText: t('buttons.yes.title'),
		cancelButtonText: t('buttons.no.title'),
		type: 'warning',
	})
		.then(async (): Promise<void> => {
			if (route.name === routeNames.deviceDetail && route.params.id === id) {
				await router.push({ name: routeNames.devices });
			}

			try {
				await devicesStore.remove({ id });
			} catch (e: any) {
				const device = await devicesStore.findById(id);

				const errorMessage = t('messages.devices.notRemoved', {
					device: useEntityTitle(device).value,
				});

				if (get(e, 'exception', null) !== null) {
					flashMessage.exception(get(e, 'exception', null), errorMessage);
				} else {
					flashMessage.error(errorMessage);
				}
			}
		})
		.catch(() => {
			flashMessage.info(
				t('messages.devices.removeCanceled', {
					property: useEntityTitle(device).value,
				})
			);
		});
};

onBeforeMount(async (): Promise<void> => {
	if (!isLoading.value && !devicesStore.firstLoadFinished(null)) {
		try {
			await devicesStore.fetch();
		} catch (e: any) {
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		}
	}
});

useMeta({
	title: t('meta.connectors.list.title'),
});
</script>
