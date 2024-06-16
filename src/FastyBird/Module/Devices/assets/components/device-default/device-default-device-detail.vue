<template>
	<div
		v-if="props.channelsLoading"
		class="p-2"
	>
		<el-skeleton
			animated
			style="--el-skeleton-circle-size: 32px"
		>
			<template #template>
				<div class="p-2">
					<div class="flex items-center h-[2rem] w-full overflow-hidden">
						<div class="flex-basis-[3rem]">
							<el-skeleton-item variant="circle" />
						</div>
						<div class="flex-basis-[30%]">
							<el-skeleton-item variant="text" />
							<el-skeleton-item
								variant="text"
								style="width: 30%"
							/>
						</div>
					</div>
				</div>
			</template>
		</el-skeleton>
	</div>

	<div
		v-if="noResults"
		class="flex flex-col justify-center h-full w-full"
	>
		<el-result>
			<template #icon>
				<fb-icon-with-child
					:size="50"
					type="primary"
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
				{{ t('texts.devices.noChannels') }}
			</template>
		</el-result>
	</div>

	<el-scrollbar
		v-else
		class="h-full"
	>
		<div class="sm:px-2 sm:pb-3">
			<device-default-device-channel
				v-for="channelData in channelsData"
				:key="channelData.channel.id"
				:device="props.deviceData.device"
				:device-properties="props.deviceData.properties"
				:device-controls="props.deviceData.controls"
				:channel-data="channelData"
				:edit-mode="props.editMode"
				@add-parameter="emit('addChannelParameter', channelData.channel.id, $event)"
			/>
		</div>
	</el-scrollbar>
</template>

<script setup lang="ts">
import { ElResult, ElScrollbar, ElSkeleton, ElSkeletonItem } from 'element-plus';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { orderBy } from 'natural-orderby';

import { FbIconWithChild } from '@fastybird/web-ui-library';
import { FasInfo, FasCube } from '@fastybird/web-ui-icons';

import { DeviceDefaultDeviceChannel } from '../../components';
import { IChannelData } from '../../types';
import { IDevicesDeviceDetailDefaultProps } from './device-default-device-detail.types';

defineOptions({
	name: 'DeviceDefaultDeviceDetail',
});

const props = withDefaults(defineProps<IDevicesDeviceDetailDefaultProps>(), {
	editMode: false,
});

const emit = defineEmits<{
	(e: 'addChannelParameter', id: string, event: Event): void;
}>();

const { t } = useI18n();

const noResults = computed<boolean>((): boolean => props.deviceData.channels.length === 0);

const channelsData = computed<IChannelData[]>((): IChannelData[] => {
	return orderBy<IChannelData>(
		props.deviceData.channels,
		[(v): string => v.channel.name ?? v.channel.identifier, (v): string => v.channel.identifier],
		['asc']
	);
});
</script>
