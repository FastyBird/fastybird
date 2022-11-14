<template>
	<fb-ui-scroll-shadow class="fb-devices-module-connectors-detail-default__container">
		<fb-ui-content
			v-if="props.connectorData.devices.length > 0"
			:ph="isExtraSmallDevice ? FbSizeTypes.NONE : FbSizeTypes.SMALL"
			:pv="isExtraSmallDevice ? FbSizeTypes.NONE : FbSizeTypes.MEDIUM"
		>
			<connector-default-connector-device
				v-for="deviceData in devicesData"
				:key="deviceData.device.id"
				:connector="props.connectorData.connector"
				:connector-properties="props.connectorData.properties"
				:connector-controls="props.connectorData.controls"
				:device-data="deviceData"
			/>
		</fb-ui-content>

		<fb-ui-no-results
			v-else
			:size="FbSizeTypes.LARGE"
			:variant="FbUiVariantTypes.PRIMARY"
			class="fb-devices-module-connectors-detail-default__no-results"
		>
			<template #icon>
				<font-awesome-icon icon="plug" />
			</template>

			<template #second-icon>
				<font-awesome-icon icon="exclamation" />
			</template>

			{{ t('texts.noDevices') }}
		</fb-ui-no-results>
	</fb-ui-scroll-shadow>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { orderBy } from 'natural-orderby';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FbUiContent, FbUiNoResults, FbUiScrollShadow, FbSizeTypes, FbUiVariantTypes } from '@fastybird/web-ui-library';

import { useBreakpoints } from '@/composables';
import { ConnectorDefaultConnectorDevice } from '@/components';
import { IDeviceData } from '@/types';
import { IConnectorsConnectorDetailDefaultProps } from '@/components/connector-default/connector-default-connector-detail.types';

const props = defineProps<IConnectorsConnectorDetailDefaultProps>();

const { t } = useI18n();
const { isExtraSmallDevice } = useBreakpoints();

const devicesData = computed<IDeviceData[]>((): IDeviceData[] => {
	return orderBy<IDeviceData>(
		props.connectorData.devices,
		[(v): string => v.device.name ?? v.device.identifier, (v): string => v.device.identifier],
		['asc']
	);
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'connector-default-connector-detail';
</style>

<i18n>
{
  "en": {
    "texts": {
      "noDevices": "This connector is without devices"
    }
  }
}
</i18n>
