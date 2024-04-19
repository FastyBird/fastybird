<template>
	<span
		:class="containerClass"
		@click="handleChange"
	>
		<slot />
	</span>
</template>

<script lang="ts" setup>
import { computed } from 'vue';

import { CHANGE_EVENT, VariantTypes } from '@fastybird/web-ui-constants';
import { useNamespace } from '@fastybird/web-ui-hooks';

import { checkTagEmits, checkTagProps } from './check-tag';

defineOptions({
	name: 'FbCheckTag',
});

const props = defineProps(checkTagProps);
const emit = defineEmits(checkTagEmits);

const ns = useNamespace('check-tag');

const containerClass = computed(() => [ns.b(), ns.is('checked', props.checked), ns.m(props.variant || VariantTypes.PRIMARY)]);

const handleChange = (): void => {
	const checked = !props.checked;

	emit(CHANGE_EVENT, checked);
	emit('update:checked', checked);
};
</script>
