<template>
	<transition :name="ns.b()" v-on="on">
		<slot />
	</transition>
</template>

<script lang="ts" setup>
import { useNamespace } from '@fastybird/web-ui-hooks';
import type { RendererElement } from 'vue';

defineOptions({
	name: 'FbCollapseTransition',
});

const ns = useNamespace('collapse-transition');

const reset = (el: RendererElement): void => {
	el.style.maxHeight = '';
	el.style.overflow = el.dataset.oldOverflow;
	el.style.paddingTop = el.dataset.oldPaddingTop;
	el.style.paddingBottom = el.dataset.oldPaddingBottom;
};

const on = {
	beforeEnter(el: RendererElement): void {
		if (!el.dataset) {
			el.dataset = {};
		}

		el.dataset.oldPaddingTop = el.style.paddingTop;
		el.dataset.oldPaddingBottom = el.style.paddingBottom;

		if (el.style.height) {
			el.dataset.elExistsHeight = el.style.height;
		}

		el.style.maxHeight = 0;
		el.style.paddingTop = 0;
		el.style.paddingBottom = 0;
	},

	enter(el: RendererElement): void {
		requestAnimationFrame((): void => {
			el.dataset.oldOverflow = el.style.overflow;

			if (el.dataset.elExistsHeight) {
				el.style.maxHeight = el.dataset.elExistsHeight;
			} else if (el.scrollHeight !== 0) {
				el.style.maxHeight = `${el.scrollHeight}px`;
			} else {
				el.style.maxHeight = 0;
			}

			el.style.paddingTop = el.dataset.oldPaddingTop;
			el.style.paddingBottom = el.dataset.oldPaddingBottom;
			el.style.overflow = 'hidden';
		});
	},

	afterEnter(el: RendererElement): void {
		el.style.maxHeight = '';
		el.style.overflow = el.dataset.oldOverflow;
	},

	enterCancelled(el: RendererElement): void {
		reset(el);
	},

	beforeLeave(el: RendererElement): void {
		if (!el.dataset) {
			el.dataset = {};
		}

		el.dataset.oldPaddingTop = el.style.paddingTop;
		el.dataset.oldPaddingBottom = el.style.paddingBottom;
		el.dataset.oldOverflow = el.style.overflow;

		el.style.maxHeight = `${el.scrollHeight}px`;
		el.style.overflow = 'hidden';
	},

	leave(el: RendererElement): void {
		if (el.scrollHeight !== 0) {
			el.style.maxHeight = 0;
			el.style.paddingTop = 0;
			el.style.paddingBottom = 0;
		}
	},

	afterLeave(el: RendererElement): void {
		reset(el);
	},

	leaveCancelled(el: RendererElement): void {
		reset(el);
	},
};
</script>
