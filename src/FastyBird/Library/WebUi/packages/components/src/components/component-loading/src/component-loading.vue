<template>
	<div :class="[ns.b(), ns.m('size-' + props.size)]">
		<fb-spinner
			:size="props.size"
			:class="ns.e('loading')"
			type="primary"
		/>

		<p :class="ns.e('content')">
			{{ props.text }}
		</p>
	</div>
</template>

<script setup lang="ts">
import { useNamespace } from 'element-plus';

import { FbSpinner } from '../../spinner';
import { componentLoadingProps } from './component-loading';

defineOptions({
	name: 'FbComponentLoading',
});

const props = defineProps(componentLoadingProps);

const ns = useNamespace('component-loading');
</script>
