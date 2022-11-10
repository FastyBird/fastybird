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
						:heading="t('headings.allConnectors')"
						:sub-heading="t('subHeadings.allConnectors', { count: items.length }, items.length)"
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
						@click="onOpenRegister"
					>
						{{ t('buttons.new.title') }}
					</fb-layout-header-button>
				</template>
			</fb-layout-header>
		</template>

		<template #content>
			<div
				v-if="isLoading"
				class="fb-devices-module-view-connectors__loading"
			>
				<fb-ui-loading-box :size="FbSizeTypes.LARGE">
					{{ t('texts.loadingConnectors') }}
				</fb-ui-loading-box>
			</div>

			<router-view
				v-if="!isLoading && isExtraSmallDevice && isPartialDetailRoute"
				:connectors="items"
			/>
		</template>

		<template
			v-if="!isLoading && (!isPartialDetailRoute || !isExtraSmallDevice)"
			#items
		>
			<connectors-list-connectors
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
				:connectors="items"
			/>

			<connectors-preview-info
				v-else
				:total="itemsCount"
				@register="onOpenRegister"
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

import { useBreakpoints, useEntityTitle, useFlashMessage, useRoutesNames } from '@/lib/composables';
import { useConnectors } from '@/lib/models';
import { IConnector } from '@/lib/models/types';
import { ConnectorsPreviewInfo, ConnectorsListConnectors } from '@/lib/components';
import { ApplicationError } from '@/lib/errors';

const emit = defineEmits<{
	(e: 'toggleMenu'): void;
}>();

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const { isExtraSmallDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const flashMessage = useFlashMessage();

const connectorsStore = useConnectors();

const itemsSearch = ref<string>('');

const itemsCount = computed<number>((): number => Object.keys(connectorsStore.data).length);

const isLoading = computed<boolean>((): boolean => connectorsStore.fetching);

const isPartialDetailRoute = computed<boolean>((): boolean => {
	return route.matched.find((matched) => matched.name === routeNames.connectorDetail) !== undefined;
});

const items = computed<IConnector[]>((): IConnector[] => {
	return orderBy<IConnector>(
		Object.values(connectorsStore.data)
			.filter((connector) => !connector.draft)
			.filter((connector) => {
				return itemsSearch.value === '' || useEntityTitle(connector).value.toLowerCase().includes(itemsSearch.value.toLowerCase());
			}),
		[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
		['asc']
	);
});

const onOpenDetail = (id: string): void => {
	router.push({
		name: routeNames.connectorDetail,
		params: {
			id,
		},
	});
};

const onOpenRegister = (): void => {
	router.push({
		name: routeNames.connectorRegister,
	});
};

const onRemove = async (id: string): Promise<void> => {
	if (route.name === routeNames.connectorDetail && route.params.id === id) {
		await router.push({ name: routeNames.connectors });
	}

	try {
		await connectorsStore.remove({ id });
	} catch (e: any) {
		const connector = await connectorsStore.findById(id);

		const errorMessage = t('messages.notRemoved', {
			connector: useEntityTitle(connector).value,
		});

		if (get(e, 'exception', null) !== null) {
			flashMessage.exception(get(e, 'exception', null), errorMessage);
		} else {
			flashMessage.error(errorMessage);
		}
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

useMeta(() => ({
	title: t('meta.title'),
}));
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'view-connectors';
</style>

<i18n>
{
  "en": {
    "meta": {
      "title": "Registered connectors"
    },
    "headings": {
      "allConnectors": "All connectors"
    },
    "subHeadings": {
      "allConnectors": "No connectors registered | One connector registered | {count} connectors registered"
    },
    "texts": {
      "loadingConnectors": "Loading connectors..."
    },
    "messages": {
      "notRemoved": "Connector {connector} couldn't be removed."
    },
    "fields": {
      "search": {
        "title": "Search connectors",
        "placeholder": "Search for connector"
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
