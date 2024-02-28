<template>
	<fb-ui-scroll-shadow class="fb-devices-module-devices-detail-default__container">
		<div
			v-if="props.deviceData.channels.length > 0"
			class="fb-devices-module-devices-detail-default__items"
		>
			<device-default-device-channel
				v-for="channelData in channelsData"
				:key="channelData.channel.id"
				:device="props.deviceData.device"
				:device-properties="props.deviceData.properties"
				:device-controls="props.deviceData.controls"
				:channel-data="channelData"
				:edit-mode="props.editMode"
			/>
		</div>

		<fb-ui-no-results
			v-else
			:size="FbSizeTypes.LARGE"
			:variant="FbUiVariantTypes.PRIMARY"
			class="fb-devices-module-devices-detail-default__no-results"
		>
			<template #icon>
				<font-awesome-icon icon="cube" />
			</template>

			<template #second-icon>
				<font-awesome-icon icon="exclamation" />
			</template>

			{{ t('texts.noChannels') }}
		</fb-ui-no-results>
	</fb-ui-scroll-shadow>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { orderBy } from 'natural-orderby';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FbUiNoResults, FbUiScrollShadow, FbSizeTypes, FbUiVariantTypes } from '@fastybird/web-ui-library';

import { DeviceDefaultDeviceChannel } from '../../components';
import { IChannelData } from '../../types';
import { IDevicesDeviceDetailDefaultProps } from './device-default-device-detail.types';

const props = withDefaults(defineProps<IDevicesDeviceDetailDefaultProps>(), {
	editMode: false,
});

const { t } = useI18n();

const channelsData = computed<IChannelData[]>((): IChannelData[] => {
	return orderBy<IChannelData>(
		props.deviceData.channels,
		[(v): string => v.channel.name ?? v.channel.identifier, (v): string => v.channel.identifier],
		['asc']
	);
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'device-default-device-detail';
</style>

<i18n>
{
  "en": {
    "texts": {
      "noChannels": "This device is without channels"
    }
  }
}
</i18n>
