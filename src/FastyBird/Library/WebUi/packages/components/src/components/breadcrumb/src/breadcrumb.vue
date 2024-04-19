<template>
	<div
		ref="breadcrumb"
		:class="ns.b()"
		aria-label="Breadcrumb"
		role="navigation"
	>
		<slot />
	</div>
</template>

<script lang="ts" setup>
import { onMounted, provide, ref } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';

import { breadcrumbContextKey } from './constants';
import { breadcrumbProps } from './breadcrumb';

defineOptions({
	name: 'FbBreadcrumb',
});

const props = defineProps(breadcrumbProps);

const ns = useNamespace('breadcrumb');
const breadcrumb = ref<HTMLDivElement>();

provide(breadcrumbContextKey, props);

onMounted((): void => {
	const items = breadcrumb.value!.querySelectorAll(`.${ns.e('item')}`);

	if (items.length) {
		items[items.length - 1].setAttribute('aria-current', 'page');
	}
});
</script>
