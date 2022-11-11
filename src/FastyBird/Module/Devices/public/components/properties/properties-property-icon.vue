<template>
	<font-awesome-icon icon="lightbulb" />
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

import { IChannelProperty, IConnectorProperty, IDeviceProperty } from '@/models/types';

interface IDevicesIconProps {
	property: IDeviceProperty | IChannelProperty | IConnectorProperty;
}

defineProps<IDevicesIconProps>();
</script>
