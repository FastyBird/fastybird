<template>
	<transition
		@before-enter="beforeEnter"
		@enter="enter"
		@leave="leave"
	>
		<div
			v-if="props.show"
			:class="[ns.b()]"
		>
			<slot />
		</div>
	</transition>
</template>

<script setup lang="ts">
import { useNamespace } from 'element-plus';

import { expandableBoxProps } from './expandable-box';

defineOptions({
	name: 'FbExpandableBox',
});

const props = defineProps(expandableBoxProps);

const ns = useNamespace('expandable-box');

const beforeEnter = (el: Element): void => {
	const element = el as HTMLElement;

	element.style.height = '0';
};

const enter = (el: Element, done: () => void): void => {
	const element = el as HTMLElement;

	element.offsetHeight;
	element.style.transition = 'height 0.5s ease-in-out';
	element.style.height = '100%';
	element.addEventListener('transitionend', done);
};

const leave = (el: Element, done: () => void): void => {
	const element = el as HTMLElement;

	element.style.transition = 'height 0.5s ease-in-out';
	element.style.height = '0';
	element.addEventListener('transitionend', done);
};
</script>
