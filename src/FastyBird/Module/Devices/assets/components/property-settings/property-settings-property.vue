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
					@click="onOpenView(PropertySettingsPropertyViewTypes.EDIT)"
				>
					<font-awesome-icon icon="pencil-alt" />
					{{ t('buttons.edit.title') }}
				</fb-ui-button>

				<fb-ui-button
					:variant="FbUiButtonVariantTypes.OUTLINE_DANGER"
					:size="FbSizeTypes.EXTRA_SMALL"
					@click="onOpenView(PropertySettingsPropertyViewTypes.REMOVE)"
				>
					<font-awesome-icon icon="trash" />
					{{ t('buttons.remove.title') }}
				</fb-ui-button>
			</div>
		</template>
	</fb-ui-item>

	<property-settings-property-edit-modal
		v-if="activeView === PropertySettingsPropertyViewTypes.EDIT"
		:connector="props.connector"
		:device="props.device"
		:channel="props.channel"
		:property="props.property"
		:transparent-bg="true"
		@close="onCloseView"
	/>

	<property-settings-property-remove
		v-if="activeView === PropertySettingsPropertyViewTypes.REMOVE"
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
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

import { FbUiButton, FbUiItem, FbSizeTypes, FbUiItemVariantTypes, FbUiButtonVariantTypes } from '@fastybird/web-ui-library';

import { useEntityTitle } from '../../composables';
import { PropertySettingsPropertyEditModal, PropertySettingsPropertyRemove } from '../../components';
import { IPropertySettingsPropertyProps, PropertySettingsPropertyViewTypes } from './property-settings-property.types';

const props = defineProps<IPropertySettingsPropertyProps>();

const { t } = useI18n();

const activeView = ref<PropertySettingsPropertyViewTypes>(PropertySettingsPropertyViewTypes.NONE);

const onOpenView = (view: PropertySettingsPropertyViewTypes): void => {
	activeView.value = view;
};

const onCloseView = (): void => {
	activeView.value = PropertySettingsPropertyViewTypes.NONE;
};
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
