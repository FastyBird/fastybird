<template>
	<fb-ui-item :variant="isExtraSmallDevice ? FbUiItemVariantTypes.LIST : FbUiItemVariantTypes.DEFAULT">
		<template #icon>
			<devices-device-icon :device="props.deviceData.device" />
		</template>

		<template #heading>
			{{ useEntityTitle(props.deviceData.device).value }}
		</template>

		<template #detail> </template>
	</fb-ui-item>
</template>

<script setup lang="ts">
import { FbUiItem, FbUiItemVariantTypes } from '@fastybird/web-ui-library';

import { useBreakpoints, useEntityTitle } from '../../composables';
import { DevicesDeviceIcon } from '../../components';
import { IDeviceDefaultChannelPropertyProps } from './connector-default-connector-device.types';

const props = defineProps<IDeviceDefaultChannelPropertyProps>();

const { isExtraSmallDevice } = useBreakpoints();
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'connector-default-connector-device';
</style>
