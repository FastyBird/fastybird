<template>
  <fb-ui-item :variant="FbUiItemVariantTypes.LIST">
    <template #heading>
      {{ useEntityTitle(props.property).value }}
    </template>

    <template #detail>
      <div class="fb-devices-module-property-settings-dynamic-property__buttons">
        <fb-ui-button
          :variant="FbUiButtonVariantTypes.OUTLINE_DEFAULT"
          :size="FbSizeTypes.EXTRA_SMALL"
          @click="onOpenView(ViewTypes.EDIT)"
        >
          <font-awesome-icon icon="pencil-alt" />
          {{ t('buttons.edit.title') }}
        </fb-ui-button>

        <fb-ui-button
          :variant="FbUiButtonVariantTypes.OUTLINE_DANGER"
          :size="FbSizeTypes.EXTRA_SMALL"
          @click="onOpenView(ViewTypes.REMOVE)"
        >
          <font-awesome-icon icon="trash" />
          {{ t('buttons.remove.title') }}
        </fb-ui-button>
      </div>
    </template>
  </fb-ui-item>

  <property-settings-property-edit-modal
    v-if="activeView === ViewTypes.EDIT"
    :connector="props.connector"
    :device="props.device"
    :channel="props.channel"
    :property="props.property"
    :transparent-bg="true"
    @close="onCloseView"
  />

  <property-settings-property-remove
    v-if="activeView === ViewTypes.REMOVE"
    :connector="props.connector"
    :device="props.device"
    :channel="props.channel"
    :property="props.property"
    :transparent-bg="true"
    @removed="onCloseView"
    @close="onCloseView"
  />
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
  FbUiButton,
  FbUiItem,
  FbSizeTypes,
  FbUiItemVariantTypes,
  FbUiButtonVariantTypes,
} from '@fastybird/web-ui-theme'

import {
  useEntityTitle,
  useRoutesNames,
} from '@/lib/composables'
import {
  IChannel,
  IChannelProperty,
  IConnector,
  IConnectorProperty,
  IDevice,
  IDeviceProperty,
} from '@/lib/models/types'
import {
  PropertySettingsPropertyEditModal,
  PropertySettingsPropertyRemove,
} from '@/lib/components'

enum ViewTypes {
  NONE = 'none',
  EDIT = 'edit',
  REMOVE = 'remove',
}

interface IDeviceSettingsDevicePropertyProps {
  connector?: IConnector
  device?: IDevice
  channel?: IChannel
  property: IChannelProperty | IConnectorProperty | IDeviceProperty
}

const props = defineProps<IDeviceSettingsDevicePropertyProps>()

const emit = defineEmits<{
  (e: 'edit', id: string): void
}>()

const { routeNames } = useRoutesNames()

const { t } = useI18n()

const activeView = ref<ViewTypes>(ViewTypes.NONE)

const onOpenView = (view: ViewTypes): void => {
  activeView.value = view
}

const onCloseView = (): void => {
  activeView.value = ViewTypes.NONE
}
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'property-settings-property';
</style>

<i18n>
{
  "en": {
    "buttons": {
      "edit": {
        "title": "Edit"
      },
      "remove": {
        "title": "Remove"
      }
    }
  }
}
</i18n>
