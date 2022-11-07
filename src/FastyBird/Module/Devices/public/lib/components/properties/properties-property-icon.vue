<template>
  <font-awesome-icon icon="lightbulb" />
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import {
  IChannelProperty,
  IConnectorProperty,
  IDeviceProperty,
} from '@/lib/models/types'

interface IDevicesIconProps {
  property: IDeviceProperty | IChannelProperty | IConnectorProperty
}

const props = defineProps<IDevicesIconProps>()
</script>
