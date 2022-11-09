<template>
  <div
    :class="['fb-devices-module-devices-list-devices__items', {'fb-devices-module-devices-list-devices__items-empty': noResults}]"
  >
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

      {{ t('texts.noDevices') }}
    </fb-ui-no-results>

    <fb-ui-swipe-actions-list
      v-else
      :items="props.items"
    >
      <template #default="{ item }">
        <fb-ui-item
          :variant="FbUiItemVariantTypes.LIST"
          class="fb-devices-module-devices-list-devices__item"
          @click="$emit('open', item.id)"
        >
          <template #icon>
            <devices-device-icon
              :device="item"
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
          class="fb-devices-module-devices-list-devices__item-remove"
          @click="close(); onOpenRemove(item)"
        >
          <font-awesome-icon icon="trash" />
        </div>
      </template>
    </fb-ui-swipe-actions-list>

    <device-settings-device-remove
      v-if="activeView === ViewTypes.REMOVE && selectedDevice !== null"
      :device="selectedDevice"
      :call-remove="false"
      @close="onCloseView"
      @confirmed="onRemoveConfirmed"
    />
  </div>
</template>

<script setup lang="ts">
import {
  computed,
  ref,
} from 'vue'
import { useI18n } from 'vue-i18n'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  FbUiItem,
  FbUiSwipeActionsList,
  FbUiNoResults,
  FbSizeTypes,
  FbUiItemVariantTypes,
  FbUiVariantTypes,
} from '@fastybird/web-ui-library'

import { useEntityTitle } from '@/lib/composables'
import { IDevice } from '@/lib/models/types'
import {
  DeviceSettingsDeviceRemove,
  DevicesDeviceIcon,
} from '@/lib/components'

enum ViewTypes {
  NONE = 'none',
  REMOVE = 'remove',
}

interface IDevicesListDevicesProps {
  items: IDevice[]
}

const props = defineProps<IDevicesListDevicesProps>()

const emit = defineEmits<{
  (e: 'open', id: string): void
  (e: 'remove', id: string): void
}>()

const { t } = useI18n()

const activeView = ref<ViewTypes>(ViewTypes.NONE)

const selectedDevice = ref<IDevice | null>(null)
const noResults = computed<boolean>((): boolean => props.items.length === 0)

const onOpenRemove = (device: IDevice): void => {
  selectedDevice.value = device

  activeView.value = ViewTypes.REMOVE
}

const onCloseView = (): void => {
  activeView.value = ViewTypes.NONE
}

const onRemoveConfirmed = (): void => {
  if (selectedDevice.value !== null) {
    emit('remove', selectedDevice.value.id)
  }

  onCloseView()
}
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'devices-list-devices';
</style>

<i18n>
{
  "en": {
    "texts": {
      "noDevices": "You don't have assigned any device"
    }
  }
}
</i18n>
