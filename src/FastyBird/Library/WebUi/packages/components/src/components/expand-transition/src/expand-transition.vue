<template>
	<transition
		:name="ns.b()"
		v-on="on"
	>
		<slot />
	</transition>
</template>

<script lang="ts" setup>
import { useNamespace } from '@fastybird/web-ui-hooks';
import type { RendererElement } from '@vue/runtime-core';

defineOptions({
	name: 'FbExpandTransition',
});

const ns = useNamespace('expand-transition');

const on = {
	beforeEnter(el: RendererElement) {
		el.style.transition = 'height 250ms ease-in-out';
		el.style.transitionProperty = 'opacity, heigh';
		el.style.overflow = 'hidden';
	},

	enter(el: RendererElement) {
		el.style.position = 'absolute';
		el.style.visibility = 'hidden';
		el.style.height = 'auto';

		const height = el.style.height;

		el.style.width = null;
		el.style.position = null;
		el.style.visibility = null;
		el.style.height = 0;

		// Trigger the animation.
		// We use `requestAnimationFrame` because we need
		// to make sure the browser has finished
		// painting after setting the `height`
		// to `0` in the line above.
		requestAnimationFrame(() => {
			el.style.height = height;
		});
	},

	afterEnter(el: RendererElement): void {
		el.style.height = 'auto';
	},

	leave(el: RendererElement): void {
		requestAnimationFrame(() => {
			el.style.height = 0;
		});
	},

	afterLeave(el: RendererElement): void {
		el.style.transition = 'height 250ms ease-in-out';
		el.style.transitionProperty = 'opacity, heigh';
		el.style.overflow = 'hidden';
	},
};
</script>
