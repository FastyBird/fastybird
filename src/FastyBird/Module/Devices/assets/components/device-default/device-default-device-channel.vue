<template>
	<fb-list>
		<template #title>
			{{ useEntityTitle(props.channelData.channel).value }}
		</template>

		<template #buttons>
			<el-button
				v-if="props.editMode"
				:icon="FasPlus"
				type="primary"
				plain
				size="small"
				@click="emit('addParameter', $event)"
			>
				{{ t('buttons.addProperty.title') }}
			</el-button>
		</template>

		<el-result v-if="!channelDynamicProperties.length">
			<template #icon>
				<fb-icon-with-child
					type="primary"
					:size="50"
				>
					<template #primary>
						<fas-cube />
					</template>
					<template #secondary>
						<fas-info />
					</template>
				</fb-icon-with-child>
			</template>

			<template #title>
				{{ t('texts.channels.noProperties') }}
			</template>
		</el-result>

		<property-default-property
			v-for="property in channelDynamicProperties"
			:key="property.id"
			:device="props.device"
			:channel="props.channelData.channel"
			:property="property"
		/>
	</fb-list>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { orderBy } from 'natural-orderby';
import { ElButton, ElResult } from 'element-plus';

import { FbIconWithChild, FbList } from '@fastybird/web-ui-library';
import { FasCube, FasInfo, FasPlus } from '@fastybird/web-ui-icons';
import { PropertyType } from '@fastybird/metadata-library';

import { useEntityTitle } from '../../composables';
import { IChannelProperty } from '../../models/types';
import { PropertyDefaultProperty } from '../../components';
import { IDeviceDefaultDeviceChannelProps } from './device-default-device-channel.types';

defineOptions({
	name: 'DeviceDefaultDeviceChannel',
});

const props = withDefaults(defineProps<IDeviceDefaultDeviceChannelProps>(), {
	editMode: false,
});

const emit = defineEmits<{
	(e: 'addParameter', event: Event): void;
}>();

const { t } = useI18n();

const channelDynamicProperties = computed<IChannelProperty[]>((): IChannelProperty[] => {
	return orderBy<IChannelProperty>(
		props.channelData.properties.filter((property) => property.type.type === PropertyType.DYNAMIC),
		[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
		['asc']
	);
});
</script>
