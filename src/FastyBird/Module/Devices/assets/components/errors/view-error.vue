<template>
	<div
		v-if="error"
		class="flex flex-col justify-center w-full h-full"
	>
		<el-result>
			<template #icon>
				<fb-icon-with-child
					type="primary"
					:size="50"
				>
					<template #primary>
						<template v-if="props.type === 'connectors' || props.type === 'connector'">
							<fas-ethernet />
						</template>
						<template v-else-if="props.type === 'devices' || props.type === 'device'">
							<fas-plug />
						</template>
						<template v-else-if="props.type === 'channels' || props.type === 'channel'">
							<fas-box />
						</template>
					</template>
					<template #secondary>
						<fas-exclamation />
					</template>
				</fb-icon-with-child>
			</template>

			<template #title>
				<h1>{{ t('headings.misc.loadingFailed') }}</h1>
			</template>

			<template #sub-title>
				<template v-if="props.type === 'connectors'">
					{{ t('messages.connectors.loadAllFailed') }}
				</template>
				<template v-if="props.type === 'connector'">
					{{ t('messages.connectors.loadFailed') }}
				</template>
				<template v-if="props.type === 'devices'">
					{{ t('messages.devices.loadAllFailed') }}
				</template>
				<template v-if="props.type === 'device'">
					{{ t('messages.devices.loadFailed') }}
				</template>
				<template v-if="props.type === 'channels'">
					{{ t('messages.channels.loadAllFailed') }}
				</template>
				<template v-if="props.type === 'channel'">
					{{ t('messages.channels.loadFailed') }}
				</template>
			</template>
		</el-result>
	</div>

	<template v-else>
		<slot />
	</template>
</template>

<script setup lang="ts">
import { onErrorCaptured, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { ElResult } from 'element-plus';

import { FasExclamation, FasBox, FasPlug, FasEthernet } from '@fastybird/web-ui-icons';
import { FbIconWithChild } from '@fastybird/web-ui-library';

import { ApplicationError } from '../../errors';
import { IViewErrorProps } from './view-error.types';

import type { ComponentPublicInstance } from 'vue';

defineOptions({
	name: 'ViewError',
});

const props = withDefaults(defineProps<IViewErrorProps>(), {
	type: null,
});

const { t } = useI18n();

const error = ref<unknown | null>(null);

onErrorCaptured((err: unknown, _vm: ComponentPublicInstance | null, info: string): boolean => {
	error.value = err;

	if (err instanceof ApplicationError) {
		console.log(err.type);
	}

	console.log(err, info);

	return false; // prevent further propagation
});
</script>
