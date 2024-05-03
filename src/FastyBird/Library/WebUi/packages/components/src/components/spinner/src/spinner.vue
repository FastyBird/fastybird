<template>
	<span :class="[ns.b(), ns.m('size-' + props.size), ns.m('variant-' + props.variant), ns.is('has-bg', props.bg)]" />
</template>

<script setup lang="ts">
import { useNamespace } from '@fastybird/web-ui-hooks';
import { spinnerProps } from './spinner';

defineOptions({
	name: 'FbSpinner',
});

const props = defineProps(spinnerProps);

const ns = useNamespace('spinner');
</script>
