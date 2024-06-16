<template>
	<div
		v-if="props.devicesLoading"
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
		v-else-if="noResults"
		class="flex flex-col justify-center h-full w-full"
	>
		<el-result>
			<template #icon>
				<fb-icon-with-child
					:size="50"
					type="primary"
				>
					<template #primary>
						<fas-plug />
					</template>
					<template #secondary>
						<fas-info />
					</template>
				</fb-icon-with-child>
			</template>

			<template #title>
				{{ t('texts.connectors.noDevices') }}
			</template>
		</el-result>
	</div>

	<el-scrollbar
		v-else
		class="h-full"
	>
		<div class="sm:px-2 sm:pb-3">
			<connector-default-connector-device
				v-for="deviceData in devicesData"
				:key="deviceData.device.id"
				:connector="props.connectorData.connector"
				:connector-properties="props.connectorData.properties"
				:connector-controls="props.connectorData.controls"
				:device-data="deviceData"
				:edit-mode="props.editMode"
			/>
		</div>
	</el-scrollbar>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { orderBy } from 'natural-orderby';
import { ElScrollbar, ElResult, ElSkeleton, ElSkeletonItem } from 'element-plus';

import { FasPlug, FasInfo } from '@fastybird/web-ui-icons';
import { FbIconWithChild } from '@fastybird/web-ui-library';

import { ConnectorDefaultConnectorDevice } from '../../components';
import { IDeviceData } from '../../types';
import { IConnectorsConnectorDetailDefaultProps } from './connector-default-connector-detail.types';

defineOptions({
	name: 'ConnectorDefaultConnectorDetail',
});

const props = defineProps<IConnectorsConnectorDetailDefaultProps>();

const { t } = useI18n();

const noResults = computed<boolean>((): boolean => props.connectorData.devices.length === 0);

const devicesData = computed<IDeviceData[]>((): IDeviceData[] => {
	return orderBy<IDeviceData>(
		props.connectorData.devices,
		[(v): string => v.device.name ?? v.device.identifier, (v): string => v.device.identifier],
		['asc']
	);
});
</script>
