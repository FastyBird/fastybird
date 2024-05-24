<template>
	<div :class="[ns.b()]">
		<div
			id="fb-app-bar-button-small"
			ref="buttonSmall"
			:class="[ns.e('buttons-small'), ns.is('expanded', hasSmallButtons)]"
		>
			<slot name="button-small" />
		</div>

		<div :class="[ns.e('header')]">
			<div
				id="fb-app-bar-heading"
				:class="[ns.e('heading')]"
			>
				<slot name="heading">
					<slot name="logo" />
				</slot>
			</div>

			<div
				id="fb-app-bar-button-left"
				:class="[ns.e('button-left')]"
			>
				<slot name="button-left" />
			</div>

			<div
				id="fb-app-bar-button-right"
				:class="[ns.e('button-right')]"
			>
				<slot name="button-right">
					<el-button
						v-if="!props.menuButtonHidden"
						:icon="props.menuCollapsed ? FasBars : FasXmark"
						size="large"
						type="primary"
						circle
						@click.prevent="emit('toggleMenu', $event)"
					/>
				</slot>
			</div>
		</div>

		<div
			id="fb-app-bar-content"
			ref="content"
			:class="[ns.e('content'), ns.is('expanded', hasContent)]"
		>
			<slot name="content" />
		</div>
	</div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, useSlots } from 'vue';
import { ElButton, useNamespace } from 'element-plus';

import { FasBars, FasXmark } from '@fastybird/web-ui-icons';

import { appBarEmits, appBarProps } from './app-bar';

defineOptions({
	name: 'FbAppBar',
});

const props = defineProps(appBarProps);
const emit = defineEmits(appBarEmits);

const slots = useSlots();

const ns = useNamespace('app-bar');

const newMutationObserver = (callback: () => void): MutationObserver | null => {
	// Skip this feature for browsers which
	// do not support ResizeObserver
	// https://caniuse.com/#search=mutationobserver
	if (typeof MutationObserver === 'undefined') {
		return null;
	}

	return new MutationObserver(callback);
};

const buttonSmall = ref<HTMLElement | null>(null);
const content = ref<HTMLElement | null>(null);

const hasSmallButtons = ref<boolean>(false);
const hasContent = ref<boolean>(false);

let mutationObserver: MutationObserver | null = null;

const mutationObserverCallback = (): void => {
	hasSmallButtons.value = 'button-small' in slots;
	hasContent.value = 'content' in slots;
};

onMounted((): void => {
	hasSmallButtons.value = 'button-small' in slots;
	hasContent.value = 'content' in slots;

	mutationObserver = newMutationObserver(mutationObserverCallback);

	if (mutationObserver !== null && buttonSmall.value !== null) {
		mutationObserver.observe(buttonSmall.value as any as Node, { childList: true });
	}

	if (mutationObserver !== null && content.value !== null) {
		mutationObserver.observe(content.value as any as Node, { childList: true });
	}
});

onUnmounted((): void => {
	if (mutationObserver !== null) {
		mutationObserver.disconnect();
	}
});
</script>
