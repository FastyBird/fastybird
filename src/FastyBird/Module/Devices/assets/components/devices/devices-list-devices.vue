<template>
	<div v-if="noResults">
		<el-result class="h-full w-full">
			<template #icon>
				<fb-icon-with-child
					type="primary"
					:size="50"
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
				{{ t('texts.misc.noDevices') }}
			</template>
		</el-result>
	</div>

	<el-scrollbar v-else>
		<fb-swipe :items="props.items">
			<template #default="{ item }">
				<fb-list-item
					:variant="ListItemVariantTypes.LIST"
					class="b-r b-r-solid cursor-pointer mr-[-1px]"
					@click="emit('open', item.id, $event)"
				>
					<template #icon>
						<devices-device-icon
							:device="item"
							:with-state="true"
						/>
					</template>

					<template #title>
						{{ useEntityTitle(item).value }}
					</template>

					<template
						v-if="item.hasComment"
						#subtitle
					>
						{{ item.comment }}
					</template>
				</fb-list-item>
			</template>

			<template #right="{ item, close }">
				<div
					:class="ns.e('button')"
					class="flex flex-col items-center justify-center p-5"
					@click="
						close();
						emit('remove', item.id, $event);
					"
				>
					<el-icon>
						<fas-trash />
					</el-icon>
				</div>
			</template>
		</fb-swipe>
	</el-scrollbar>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElResult, ElIcon, ElScrollbar, useNamespace } from 'element-plus';

import { FasInfo, FasPlug, FasTrash } from '@fastybird/web-ui-icons';
import { FbListItem, FbSwipe, FbIconWithChild, ListItemVariantTypes } from '@fastybird/web-ui-library';

import { useEntityTitle } from '../../composables';
import { DevicesDeviceIcon } from '../../components';
import { IDevicesListDevicesProps } from './devices-list-devices.types';

defineOptions({
	name: 'DevicesListDevices',
});

const props = defineProps<IDevicesListDevicesProps>();

const emit = defineEmits<{
	(e: 'open', id: string, event: Event): void;
	(e: 'remove', id: string, event: Event): void;
}>();

const ns = useNamespace('devices-list-devices');
const { t } = useI18n();

const noResults = computed<boolean>((): boolean => props.items.length === 0);
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'devices-list-devices.scss';
</style>
