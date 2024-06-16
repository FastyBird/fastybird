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
						<fas-box />
					</template>
					<template #secondary>
						<fas-exclamation />
					</template>
				</fb-icon-with-child>
			</template>

			<template #title> <h1>An error occurred</h1> </template>
			<template #sub-title> Connector could not be loaded </template>
		</el-result>
	</div>

	<template v-else>
		<slot />
	</template>
</template>

<script setup lang="ts">
import { onErrorCaptured, ref } from 'vue';
import { ElResult } from 'element-plus';

import { FasExclamation, FasBox } from '@fastybird/web-ui-icons';
import { FbIconWithChild } from '@fastybird/web-ui-library';

import { ApplicationError } from '../../errors';

import type { ComponentPublicInstance } from 'vue';

defineOptions({
	name: 'ChannelError',
});

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
