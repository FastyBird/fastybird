<template>
	<div :class="['fb-devices-module-connectors-list-connectors__items', { 'fb-devices-module-connectors-list-connectors__items-empty': noResults }]">
		<fb-ui-no-results
			v-if="noResults"
			:size="FbSizeTypes.LARGE"
			:variant="FbUiVariantTypes.PRIMARY"
		>
			<template #icon>
				<font-awesome-icon icon="plug" />
			</template>

			<template #second-icon>
				<font-awesome-icon icon="exclamation" />
			</template>

			{{ t('texts.noConnectors') }}
		</fb-ui-no-results>

		<fb-ui-swipe-actions-list
			v-else
			:items="props.items"
		>
			<template #default="{ item }">
				<fb-ui-item
					:variant="FbUiItemVariantTypes.LIST"
					class="fb-devices-module-connectors-list-connectors__item"
					@click="emit('open', item.id)"
				>
					<template #icon>
						<connectors-connector-icon
							:connector="item"
							:with-state="true"
						/>
					</template>

					<template #heading>
						{{ useEntityTitle(item).value }}
					</template>

					<template
						v-if="item.hasComment"
						#subheading
					>
						{{ item.comment }}
					</template>
				</fb-ui-item>
			</template>

			<template #right="{ item, close }">
				<div
					class="fb-devices-module-connectors-list-connectors__item-remove"
					@click="
						close();
						onOpenRemove(item);
					"
				>
					<font-awesome-icon icon="trash" />
				</div>
			</template>
		</fb-ui-swipe-actions-list>

		<connector-settings-connector-remove
			v-if="activeView === ConnectorsListConnectorsViewTypes.REMOVE && selectedConnector !== null"
			:connector="selectedConnector"
			:call-remove="false"
			@close="onCloseView"
			@confirmed="onRemoveConfirmed"
		/>
	</div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FbUiItem, FbUiSwipeActionsList, FbUiNoResults, FbSizeTypes, FbUiItemVariantTypes, FbUiVariantTypes } from '@fastybird/web-ui-library';

import { useEntityTitle } from '../../composables';
import { IConnector } from '../../models/types';
import { ConnectorSettingsConnectorRemove, ConnectorsConnectorIcon } from '../../components';
import { IConnectorsListConnectorsProps, ConnectorsListConnectorsViewTypes } from './connectors-list-connectors.types';

const props = defineProps<IConnectorsListConnectorsProps>();

const emit = defineEmits<{
	(e: 'open', id: string): void;
	(e: 'remove', id: string): void;
}>();

const { t } = useI18n();

const activeView = ref<ConnectorsListConnectorsViewTypes>(ConnectorsListConnectorsViewTypes.NONE);

const selectedConnector = ref<IConnector | null>(null);
const noResults = computed<boolean>((): boolean => props.items.length === 0);

const onOpenRemove = (connector: IConnector): void => {
	selectedConnector.value = connector;

	activeView.value = ConnectorsListConnectorsViewTypes.REMOVE;
};

const onCloseView = (): void => {
	activeView.value = ConnectorsListConnectorsViewTypes.NONE;
};

const onRemoveConfirmed = (): void => {
	if (selectedConnector.value !== null) {
		emit('remove', selectedConnector.value.id);
	}

	onCloseView();
};
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'connectors-list-connectors';
</style>

<i18n>
{
  "en": {
    "texts": {
      "noConnectors": "You don't have assigned any connector"
    }
  }
}
</i18n>
