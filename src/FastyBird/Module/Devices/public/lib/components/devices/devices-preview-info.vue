<template>
  <fb-ui-content
    :ml="FbSizeTypes.MEDIUM"
    class="fb-devices-module-devices-preview-info__container"
  >
    <fb-ui-media-item>
      <template #left>
        <font-awesome-icon
          icon="plug"
          size="2x"
        />
      </template>

      <template #heading>
        {{ t('headings.allDevices') }}
      </template>

      <template #description>
        {{ t('subHeadings.allDevices', { count: total }, total) }}
      </template>
    </fb-ui-media-item>

    <fb-ui-media-item>
      <template #left>
        <font-awesome-icon
          icon="plus"
          size="2x"
        />
      </template>

      <template #heading>
        {{ t('headings.connectDevice') }}
      </template>

      <template #description>
        {{ t('subHeadings.connectDevice') }}
      </template>

      <template #action>
        <fb-ui-button
          :variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
          :size="FbSizeTypes.MEDIUM"
          @click="$emit('connect', $emit)"
        >
          {{ t('buttons.add.title') }}
        </fb-ui-button>
      </template>
    </fb-ui-media-item>

    <fb-ui-media-item>
      <template #left>
        <font-awesome-icon
          icon="sync-alt"
          size="2x"
        />
      </template>

      <template #heading>
        {{ t('headings.syncDevices') }}
      </template>

      <template #description>
        {{ t('subHeadings.syncDevices') }}
      </template>

      <template #action>
        <fb-ui-button
          :variant="FbUiButtonVariantTypes.OUTLINE_DEFAULT"
          :size="FbSizeTypes.MEDIUM"
          @click="$emit('synchronise', $emit)"
        >
          {{ t('buttons.sync.title') }}
        </fb-ui-button>
      </template>
    </fb-ui-media-item>
  </fb-ui-content>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  FbUiButton,
  FbUiMediaItem,
  FbUiContent,
  FbSizeTypes,
  FbUiButtonVariantTypes,
} from '@fastybird/web-ui-theme'

interface IDevicesDesktopInfoProps {
  total: number
}

const { total } = defineProps<IDevicesDesktopInfoProps>()

const { t } = useI18n()
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'devices-preview-info';
</style>

<i18n>
{
  "en": {
    "headings": {
      "allDevices": "All devices",
      "connectDevice": "Connect new device",
      "syncDevices": "Sync devices"
    },
    "subHeadings": {
      "allDevices": "No devices registered | One device registered | {count} devices registered",
      "connectDevice": "Register new device to the system",
      "syncDevices": "Sync all devices with server"
    },
    "buttons": {
      "add": {
        "title": "Connect new device"
      },
      "sync": {
        "title": "Synchronise"
      }
    }
  }
}
</i18n>
