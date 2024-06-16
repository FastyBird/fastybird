<template>
	<fb-list-item :variant="isXSDevice ? ListItemVariantTypes.LIST : ListItemVariantTypes.DEFAULT">
		<template #icon>
			<devices-device-icon :device="props.deviceData.device" />
		</template>

		<template #title>
			{{ useEntityTitle(props.deviceData.device).value }}
		</template>
	</fb-list-item>
</template>

<script setup lang="ts">
import { FbListItem, ListItemVariantTypes } from '@fastybird/web-ui-library';

import { useBreakpoints, useEntityTitle } from '../../composables';
import { DevicesDeviceIcon } from '../../components';
import { IDeviceDefaultChannelPropertyProps } from './connector-default-connector-device.types';

defineOptions({
	name: 'ConnectorDefaultConnectorDevice',
});

const props = defineProps<IDeviceDefaultChannelPropertyProps>();

const { isXSDevice } = useBreakpoints();
</script>
