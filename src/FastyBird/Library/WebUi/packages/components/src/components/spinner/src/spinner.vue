<template>
	<span :class="[ns.b(), ns.m('size-' + props.size), ns.m('type-' + props.type), ns.is('has-bg', props.bg)]" />
</template>

<script setup lang="ts">
import { useNamespace } from 'element-plus';

import { spinnerProps } from './spinner';

defineOptions({
	name: 'FbSpinner',
});

const props = defineProps(spinnerProps);

const ns = useNamespace('spinner');
</script>
