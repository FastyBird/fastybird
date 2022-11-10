<template>
	<fb-ui-item :variant="isExtraSmallDevice ? FbUiItemVariantTypes.LIST : FbUiItemVariantTypes.DEFAULT">
		<template #icon>
			<properties-property-icon :property="props.property" />
		</template>

		<template #heading>
			{{ useEntityTitle(props.property).value }}
		</template>

		<template #detail>
			<template v-if="props.property.dataType === DataType.SWITCH || (props.property.dataType === DataType.BOOLEAN && props.property.settable)">
				<actors-property-actor-switch
					:device="props.device"
					:channel="props.channel"
					:property="props.property"
				/>
			</template>

			<template v-else-if="props.property.dataType === DataType.BOOLEAN && !props.property.settable">
				<span class="fb-devices-module-property-default-property__value">
					<template v-if="!isReady || !wsStatus">
						{{ t('states.notAvailable') }}
					</template>
					<template v-else-if="value">
						{{ t('states.on') }}
					</template>
					<template v-else>
						{{ t('states.off') }}
					</template>
				</span>
			</template>

			<template v-else>
				<span class="fb-devices-module-property-default-property__value">
					<template v-if="!isReady || !wsStatus">
						{{ t('states.notAvailable') }}
					</template>
					<template v-else>
						{{ value }}
					</template>
				</span>
				<span
					v-if="props.property.unit !== null"
					class="fb-devices-module-property-default-property__unit"
				>
					{{ props.property.unit }}
				</span>
			</template>
		</template>
	</fb-ui-item>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

import { FbUiItem, FbUiItemVariantTypes } from '@fastybird/web-ui-library';
import { DataType, PropertyType } from '@fastybird/metadata-library';
import { useWsExchangeClient } from '@fastybird/ws-exchange-plugin';

import { useBreakpoints, useConnectorState, useEntityTitle, useDeviceState } from '@/lib/composables';
import { IChannel, IChannelProperty, IConnector, IConnectorProperty, IDeviceProperty, IDevice } from '@/types/devices-module';
import { ActorsPropertyActorSwitch, PropertiesPropertyIcon } from '@/lib/components';

interface IDeviceDefaultChannelPropertyProps {
	connector?: IConnector;
	device?: IDevice;
	channel?: IChannel;
	property: IChannelProperty | IConnectorProperty | IDeviceProperty;
}

const props = defineProps<IDeviceDefaultChannelPropertyProps>();

const { t } = useI18n();
const { isExtraSmallDevice } = useBreakpoints();

const { isReady: isConnectorReady } = props.connector !== undefined ? useConnectorState(props.connector) : { isReady: undefined };
const { isReady: isDeviceReady } = props.device !== undefined ? useDeviceState(props.device) : { isReady: undefined };

const isReady = computed<boolean>((): boolean => {
	if (isConnectorReady !== undefined) {
		return isConnectorReady.value;
	}

	if (isDeviceReady !== undefined) {
		return isDeviceReady.value;
	}

	return false;
});

const value = computed<any>((): any => {
	if (props.property.type.type === PropertyType.VARIABLE) {
		return props.property.value;
	}

	return props.property.actualValue;
});

const { status: wsStatus } = useWsExchangeClient();
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'property-default-property';
</style>

<i18n>
{
  "en": {
    "states": {
      "actual": "Actual",
      "on": "On",
      "off": "Off",
      "notAvailable": "N/A"
    }
  }
}
</i18n>
