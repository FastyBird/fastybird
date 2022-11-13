<template>
	<fb-ui-items-container class="fb-devices-module-device-default-device-channel__container">
		<template #heading>
			{{ useEntityTitle(props.channelData.channel).value }}
		</template>

		<fb-ui-content
			:mh="isExtraSmallDevice ? FbSizeTypes.NONE : FbSizeTypes.SMALL"
			:mv="isExtraSmallDevice ? FbSizeTypes.NONE : FbSizeTypes.MEDIUM"
		>
			<property-default-property
				v-for="property in channelDynamicProperties"
				:key="property.id"
				:device="props.device"
				:channel="props.channelData.channel"
				:property="property"
			/>
		</fb-ui-content>

		<fb-ui-no-results
			v-if="!channelDynamicProperties.length"
			:size="FbSizeTypes.LARGE"
			:variant="FbUiVariantTypes.PRIMARY"
		>
			<template #icon>
				<font-awesome-icon icon="cube" />
			</template>

			<template #second-icon>
				<font-awesome-icon icon="exclamation" />
			</template>

			{{ t('texts.noProperties') }}
		</fb-ui-no-results>

		<template #buttons>
			<fb-ui-content :mr="FbSizeTypes.SMALL">
				<fb-ui-button
					v-if="props.editMode"
					:variant="FbUiButtonVariantTypes.OUTLINE_PRIMARY"
					:size="FbSizeTypes.EXTRA_SMALL"
					@click="onOpenView(ViewTypes.ADD_PARAMETER)"
				>
					<template #icon>
						<font-awesome-icon icon="plus" />
					</template>
					{{ t('buttons.addProperty.title') }}
				</fb-ui-button>
			</fb-ui-content>
		</template>
	</fb-ui-items-container>

	<property-settings-property-add-modal
		v-if="activeView === ViewTypes.ADD_PARAMETER && newProperty !== null"
		:property="newProperty"
		:channel="props.channelData.channel"
		:device="props.device"
		@close="onCloseAddProperty"
	/>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { orderBy } from 'natural-orderby';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
	FbUiContent,
	FbUiItemsContainer,
	FbUiNoResults,
	FbUiButton,
	FbSizeTypes,
	FbUiVariantTypes,
	FbUiButtonVariantTypes,
} from '@fastybird/web-ui-library';
import { DataType, PropertyType } from '@fastybird/metadata-library';

import { useBreakpoints, useEntityTitle, useUuid } from '@/composables';
import { useChannelProperties } from '@/models';
import { IChannelProperty, IDevice, IDeviceControl, IDeviceProperty } from '@/models/types';
import { PropertyDefaultProperty, PropertySettingsPropertyAddModal } from '@/components';
import { IChannelData } from '@/types';

enum ViewTypes {
	NONE = 'none',
	ADD_PARAMETER = 'addParameter',
}

interface IDeviceDefaultDeviceChannelProps {
	device: IDevice;
	deviceControls: IDeviceControl[];
	deviceProperties: IDeviceProperty[];
	channelData: IChannelData;
	editMode?: boolean;
}

const props = withDefaults(defineProps<IDeviceDefaultDeviceChannelProps>(), {
	editMode: false,
});

const { t } = useI18n();
const { generate: generateUuid } = useUuid();
const { isExtraSmallDevice } = useBreakpoints();

const propertiesStore = useChannelProperties();

const activeView = ref<ViewTypes>(ViewTypes.NONE);

const channelDynamicProperties = computed<IChannelProperty[]>((): IChannelProperty[] => {
	return orderBy<IChannelProperty>(
		props.channelData.properties.filter((property) => property.type.type === PropertyType.DYNAMIC),
		[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
		['asc']
	);
});

const newPropertyId = ref<string | null>(null);
const newProperty = computed<IChannelProperty | null>((): IChannelProperty | null =>
	newPropertyId.value ? propertiesStore.findById(newPropertyId.value) : null
);

const onOpenView = async (view: ViewTypes): Promise<void> => {
	if (view === ViewTypes.ADD_PARAMETER) {
		const { id } = await propertiesStore.add({
			channel: props.channelData.channel,
			type: { source: props.channelData.channel.type.source, type: PropertyType.DYNAMIC, parent: 'channel' },
			draft: true,
			data: {
				identifier: generateUuid(),
				dataType: DataType.UNKNOWN,
			},
		});

		newPropertyId.value = id;
	}

	activeView.value = view;
};

const onCloseView = async (): Promise<void> => {
	if (activeView.value === ViewTypes.ADD_PARAMETER && newProperty.value?.draft) {
		await propertiesStore.remove({ id: newProperty.value.id });
		newPropertyId.value = null;
	}

	activeView.value = ViewTypes.NONE;
};

const onCloseAddProperty = (saved: boolean): void => {
	if (saved) {
		activeView.value = ViewTypes.NONE;
	} else {
		onCloseView();
	}
};
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'device-default-device-channel';
</style>

<i18n>
{
  "en": {
    "texts": {
      "noProperties": "This channel is without properties"
    },
    "buttons": {
      "addProperty": {
        "title": "Add parameter"
      }
    }
  }
}
</i18n>
