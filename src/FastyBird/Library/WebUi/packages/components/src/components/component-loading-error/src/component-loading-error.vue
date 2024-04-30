<template>
	<div :class="[ns.b(), ns.m('size-' + props.size)]">
		<div :class="ns.e('icon')">
			<fb-icon>
				<component :is="TypeComponentsMap.error" />
			</fb-icon>
		</div>

		<p :class="ns.e('content')">
			{{ props.text }}
		</p>
	</div>
</template>

<script setup lang="ts">
import { useNamespace } from '@fastybird/web-ui-hooks';
import { TypeComponentsMap } from '@fastybird/web-ui-utils';

import { FbIcon } from '../../icon';
import { componentLoadingErrorProps } from './component-loading-error';

defineOptions({
	name: 'FbComponentLoadingError',
});

const props = defineProps(componentLoadingErrorProps);

const ns = useNamespace('component-loading-error');
</script>
