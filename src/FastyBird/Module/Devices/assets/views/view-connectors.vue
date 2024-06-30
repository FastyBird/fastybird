<template>
	<fb-app-bar-heading
		v-if="isXSDevice && !isPartialDetailRoute"
		teleport
	>
		<template #icon>
			<fas-ethernet />
		</template>

		<template #title>
			{{ t('headings.connectors.allConnectors') }}
		</template>

		<template #subtitle>
			{{ t('subHeadings.connectors.allConnectors', { count: items.length }, items.length) }}
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
			<connectors-list-connectors
				v-loading="isLoading"
				:element-loading-text="t('texts.misc.loadingConnectors')"
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

			<connectors-preview-info
				v-else
				:total="itemsCount"
				class="flex-grow h-full"
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

import { FasEthernet } from '@fastybird/web-ui-icons';
import { FbAppBarHeading, FbAppBarButton, AppBarButtonAlignTypes } from '@fastybird/web-ui-library';

import { useBreakpoints, useEntityTitle, useFlashMessage, useRoutesNames } from '../composables';
import { useConnectorControls, useConnectorProperties, useConnectors } from '../models';
import { IConnector } from '../models/types';
import { ConnectorsPreviewInfo, ConnectorsListConnectors, ViewError } from '../components';
import { ApplicationError } from '../errors';

defineOptions({
	name: 'ViewConnectors',
});

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
useMeta({
	title: t('meta.connectors.list.title'),
});

const { isXSDevice } = useBreakpoints();
const { routeNames } = useRoutesNames();
const flashMessage = useFlashMessage();

const connectorsStore = useConnectors();
const connectorControlsStore = useConnectorControls();
const connectorPropertiesStore = useConnectorProperties();

const itemsSearch = ref<string>('');

const itemsCount = computed<number>((): number => connectorsStore.findAll().length);

const isLoading = computed<boolean>((): boolean => connectorsStore.fetching());

const isPartialDetailRoute = computed<boolean>((): boolean => {
	return route.matched.find((matched) => matched.name === routeNames.connectorDetail) !== undefined;
});

const items = computed<IConnector[]>((): IConnector[] => {
	return orderBy<IConnector>(
		connectorsStore
			.findAll()
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

const onSynchronise = (): void => {
	// TODO: handle connectors refresh
};

const onRemove = async (id: string): Promise<void> => {
	const connector = await connectorsStore.findById(id);

	if (connector === null) {
		return;
	}

	ElMessageBox.confirm(t('messages.connectors.confirmRemove', { connector: useEntityTitle(connector).value }), t('headings.connectors.remove'), {
		confirmButtonText: t('buttons.yes.title'),
		cancelButtonText: t('buttons.no.title'),
		type: 'warning',
	})
		.then(async (): Promise<void> => {
			if (route.name === routeNames.connectorDetail && route.params.id === id) {
				await router.push({ name: routeNames.connectors });
			}

			try {
				await connectorsStore.remove({ id });
			} catch (e: any) {
				const connector = await connectorsStore.findById(id);

				const errorMessage = t('messages.connectors.notRemoved', {
					connector: useEntityTitle(connector).value,
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
				t('messages.connectors.removeCanceled', {
					property: useEntityTitle(connector).value,
				})
			);
		});
};

onBeforeMount(async (): Promise<void> => {
	try {
		await connectorsStore.fetch({ refresh: !connectorsStore.firstLoadFinished() });

		const connectors = connectorsStore.findAll();

		for (const connector of connectors) {
			await connectorPropertiesStore.fetch({ connector, refresh: false });
			await connectorControlsStore.fetch({ connector, refresh: false });
		}
	} catch (e: any) {
		throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
	}
});
</script>
