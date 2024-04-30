<template>
	<transition :name="props.animation ? ns.b('bounce') : 'none'">
		<div v-if="props.show" :class="[ns.b(), ns.m('size-' + props.size), ns.m('variant-' + props.variant), ns.is('fullscreen', props.fullScreen)]">
			<div v-if="'icon' in $slots" :class="ns.e('icon')">
				<slot name="icon" />
			</div>

			<template v-if="props.spinner">
				<component :is="props.spinner" v-if="typeof props.spinner === 'string'" class="fb-icon" />
				<template v-else-if="typeof props.spinner === 'boolean'">
					<fb-spinner :size="props.size" :variant="props.variant" />
				</template>
				<template v-else>
					{{ props.spinner }}
				</template>
			</template>

			<div v-if="'default' in $slots" :class="ns.e('content')">
				<slot />
			</div>
		</div>
	</transition>
</template>

<script setup lang="ts">
import { useNamespace } from '@fastybird/web-ui-hooks';

import { FbSpinner } from '../../spinner';
import { loadingBoxProps } from './loading-box';

defineOptions({
	name: 'FbLoadingBox',
});

const props = defineProps(loadingBoxProps);

const ns = useNamespace('loading-box');
</script>
