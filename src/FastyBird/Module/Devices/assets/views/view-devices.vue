<template>
	<fb-layout-content
		v-model="itemsSearch"
		:search-placeholder="t('fields.search.placeholder')"
		with-search
	>
		<template
			v-if="isExtraSmallDevice"
			#header
		>
			<fb-layout-header menu-button-hidden>
				<template
					v-if="!isPartialDetailRoute"
					#heading
				>
					<fb-layout-header-heading
						:heading="t('headings.allDevices')"
						:sub-heading="t('subHeadings.allDevices', { count: items.length }, items.length)"
						:teleport="false"
					/>
				</template>

				<template
					v-if="!isPartialDetailRoute"
					#button-right
				>
					<fb-layout-header-icon
						:teleport="false"
						right
					>
						<font-awesome-icon icon="plug" />
					</fb-layout-header-icon>
				</template>

				<template
					v-if="!isPartialDetailRoute"
					#button-small
				>
					<fb-layout-header-button
						:teleport="false"
						:action-type="FbMenuItemTypes.BUTTON"
						small
						left
						@click="emit('toggleMenu')"
					>
						{{ t('buttons.menu.title') }}
					</fb-layout-header-button>

					<fb-layout-header-button
						:teleport="false"
						:action-type="FbMenuItemTypes.BUTTON"
						small
						right
						@click="onOpenConnect"
					>
						{{ t('buttons.new.title') }}
					</fb-layout-header-button>
				</template>
			</fb-layout-header>
		</template>

		<template
			v-if="isLoading || (!isLoading && isExtraSmallDevice && isPartialDetailRoute)"
			#content
		>
			<div
				v-if="isLoading"
				class="fb-devices-module-view-devices__loading"
			>
				<fb-ui-loading-box :size="FbSizeTypes.LARGE">
					{{ t('texts.loadingDevices') }}
				</fb-ui-loading-box>
			</div>

			<router-view
				v-if="!isLoading && isExtraSmallDevice && isPartialDetailRoute"
				:devices="items"
			/>
		</template>

		<template
			v-if="!isLoading && (!isPartialDetailRoute || !isExtraSmallDevice)"
			#items
		>
			<devices-list-devices
				:items="items"
				@open="onOpenDetail"
				@remove="onRemove"
			/>
		</template>

		<template
			v-if="!isLoading && !isExtraSmallDevice"
			#preview
		>
			<router-view
				v-if="isPartialDetailRoute"
				:devices="items"
			/>

			<devices-preview-info
				v-else
				:total="itemsCount"
				@connect="onOpenConnect"
			/>
		</template>

		<template #footer>
			<fb-layout-footer />
		</template>
	</fb-layout-content>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRoute, useRouter } from 'vue-router';
import get from 'lodash/get';
import { orderBy } from 'natural-orderby';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
	FbLayoutContent,
	FbLayoutFooter,
	FbUiLoadingBox,
	FbLayoutHeader,
	FbLayoutHeaderIcon,
	FbLayoutHeaderHeading,
	FbLayoutHeaderButton,
	FbMenuItemTypes,
	FbSizeTypes,
} from '@fastybird/web-ui-library';

import { useBreakpoints, useEntityTitle, useFlashMessage, useRoutesNames } from '@/composables';
import { useDevices } from '@/models';
import { IDevice } from '@/models/types';
import { DevicesPreviewInfo, DevicesListDevices } from '@/components';
import { ApplicationError } from '@/errors';

const emit = defineEmits<{
	(e: 'toggleMenu'): void;
}>();

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const { isExtraSmallDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const flashMessage = useFlashMessage();

const devicesStore = useDevices();

const itemsSearch = ref<string>('');

const itemsCount = computed<number>((): number => Object.keys(devicesStore.data).length);

const isLoading = computed<boolean>((): boolean => devicesStore.fetching);

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

const onOpenConnect = (): void => {
	router.push({
		name: routeNames.deviceConnect,
	});
};

const onRemove = async (id: string): Promise<void> => {
	if (route.name === routeNames.deviceDetail && route.params.id === id) {
		await router.push({ name: routeNames.devices });
	}

	try {
		await devicesStore.remove({ id });
	} catch (e: any) {
		const device = await devicesStore.findById(id);

		const errorMessage = t('messages.notRemoved', {
			device: useEntityTitle(device).value,
		});

		if (get(e, 'exception', null) !== null) {
			flashMessage.exception(get(e, 'exception', null), errorMessage);
		} else {
			flashMessage.error(errorMessage);
		}
	}
};

onBeforeMount(async (): Promise<void> => {
	if (!isLoading.value && !devicesStore.firstLoadFinished) {
		try {
			await devicesStore.fetch({ withChannels: true });
		} catch (e: any) {
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		}
	}
});

useMeta(() => ({
	title: t('meta.title'),
}));
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'view-devices';
</style>

<i18n>
{
  "en": {
    "meta": {
      "title": "Registered devices"
    },
    "headings": {
      "allDevices": "All devices"
    },
    "subHeadings": {
      "allDevices": "No devices registered | One device registered | {count} devices registered"
    },
    "texts": {
      "loadingDevices": "Loading devices..."
    },
    "messages": {
      "notRemoved": "Device {device} couldn't be removed."
    },
    "fields": {
      "search": {
        "title": "Search devices",
        "placeholder": "Search for device"
      }
    },
    "buttons": {
      "menu": {
        "title": "Menu"
      },
      "new": {
        "title": "New"
      }
    }
  }
}
</i18n>
