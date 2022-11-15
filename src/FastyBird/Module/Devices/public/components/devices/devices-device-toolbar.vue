<template>
	<fb-layout-preview-toolbar class="fb-devices-module-devices-device-toolbar__container">
		<template #left>
			<fb-ui-button
				:variant="FbUiButtonVariantTypes.LINK_DEFAULT"
				:size="FbSizeTypes.EXTRA_SMALL"
				@click="emit('close')"
			>
				<font-awesome-icon icon="times" />
				{{ t('buttons.close.title') }}
			</fb-ui-button>

			<fb-ui-button
				v-if="!props.editMode"
				:variant="FbUiButtonVariantTypes.LINK_DEFAULT"
				:size="FbSizeTypes.EXTRA_SMALL"
				@click="emit('toggleEdit')"
			>
				<font-awesome-icon icon="pencil-alt" />
				{{ t('buttons.edit.title') }}
			</fb-ui-button>

			<fb-ui-button
				v-if="props.editMode"
				:variant="FbUiButtonVariantTypes.LINK"
				:size="FbSizeTypes.EXTRA_SMALL"
				@click="emit('toggleEdit')"
			>
				<font-awesome-icon icon="check" />
				{{ t('buttons.done.title') }}
			</fb-ui-button>
		</template>

		<template #right>
			<i18n-t
				keypath="misc.paging"
				tag="div"
				class="fb-devices-module-devices-device-toolbar__paging"
			>
				<template #page>
					<span class="fb-devices-module-devices-device-toolbar__paging-page">
						{{ props.page }}
					</span>
				</template>

				<template #total>
					<span class="fb-devices-module-devices-device-toolbar__paging-total">
						{{ props.total }}
					</span>
				</template>
			</i18n-t>

			<fb-ui-button
				:disabled="props.page <= 1"
				:variant="FbUiButtonVariantTypes.LINK_DEFAULT"
				:size="FbSizeTypes.EXTRA_SMALL"
				@click="emit('previous')"
			>
				<font-awesome-icon icon="angle-left" />
			</fb-ui-button>

			<fb-ui-button
				:disabled="props.page >= props.total"
				:variant="FbUiButtonVariantTypes.LINK_DEFAULT"
				:size="FbSizeTypes.EXTRA_SMALL"
				@click="emit('next')"
			>
				<font-awesome-icon icon="angle-right" />
			</fb-ui-button>
		</template>
	</fb-layout-preview-toolbar>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FbLayoutPreviewToolbar, FbUiButton, FbSizeTypes, FbUiButtonVariantTypes } from '@fastybird/web-ui-library';
import { IDevicesPreviewToolbarProps } from '@/components/devices/devices-device-toolbar.types';

const props = withDefaults(defineProps<IDevicesPreviewToolbarProps>(), {
	editMode: false,
});

const emit = defineEmits<{
	(e: 'toggleEdit'): void;
	(e: 'previous'): void;
	(e: 'next'): void;
	(e: 'close'): void;
}>();

const { t } = useI18n();
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'devices-device-toolbar';
</style>

<i18n>
{
  "en": {
    "buttons": {
      "close": {
        "title": "Close"
      },
      "edit": {
        "title": "Edit"
      },
      "done": {
        "title": "Done"
      }
    },
    "misc": {
      "or": "or",
      "paging": "{page} of {total}"
    }
  }
}
</i18n>
