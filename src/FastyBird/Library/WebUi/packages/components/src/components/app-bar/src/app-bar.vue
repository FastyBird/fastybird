<template>
	<div :class="[ns.b()]">
		<div id="fb-app-bar-button-small" ref="buttonSmall" :class="[ns.e('buttons-small'), ns.is('expanded', hasSmallButtons)]">
			<slot name="button-small" />
		</div>

		<div :class="[ns.e('header')]">
			<div id="fb-app-bar-header" :class="[ns.e('heading')]">
				<slot name="heading">
					<slot name="logo" />
				</slot>
			</div>

			<div id="fb-app-bar-button-left" :class="[ns.e('button-left')]">
				<slot name="button-left" />
			</div>

			<div id="fb-app-bar-button-right" :class="[ns.e('button-right')]">
				<slot name="button-right">
					<fb-button
						v-if="!props.menuButtonHidden"
						:size="ComponentSizeTypes.LARGE"
						:icon="props.menuCollapsed ? FasBars : FasXmark"
						:variant="VariantTypes.PRIMARY"
						circle
						@click.prevent="emit('toggleMenu', $event)"
					/>
				</slot>
			</div>
		</div>

		<div id="fb-app-bar-content" ref="subContent" :class="[ns.e('content'), ns.is('expanded', hasSubContent)]">
			<slot name="content" />
		</div>
	</div>
</template>

<script lang="ts" setup>
import { onMounted, onUnmounted, ref } from 'vue';

import { ComponentSizeTypes, VariantTypes } from '@fastybird/web-ui-constants';
import { useNamespace } from '@fastybird/web-ui-hooks';
import { FasBars, FasXmark } from '@fastybird/web-ui-icons';

import { FbButton } from '../../button';

import { appBarEmits, appBarProps } from './app-bar';

defineOptions({
	name: 'FbAppBar',
});

const props = defineProps(appBarProps);
const emit = defineEmits(appBarEmits);

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
const subContent = ref<HTMLElement | null>(null);

const hasSmallButtons = ref<boolean>(false);
const hasSubContent = ref<boolean>(false);

let mutationObserver: MutationObserver | null = null;

const mutationObserverCallback = (): void => {
	hasSmallButtons.value = buttonSmall.value !== null && buttonSmall.value?.children.length > 0;
	hasSubContent.value = subContent.value !== null && (subContent.value?.childElementCount > 0 || subContent.value.textContent !== null);
};

onMounted((): void => {
	hasSmallButtons.value = buttonSmall.value !== null && buttonSmall.value?.children.length > 0;
	hasSubContent.value = subContent.value !== null && (subContent.value?.childElementCount > 0 || subContent.value.textContent !== null);

	mutationObserver = newMutationObserver(mutationObserverCallback);

	if (mutationObserver !== null && buttonSmall.value !== null) {
		mutationObserver.observe(buttonSmall.value, { childList: true });
	}

	if (mutationObserver !== null && subContent.value !== null) {
		mutationObserver.observe(subContent.value, { childList: true });
	}
});

onUnmounted((): void => {
	if (mutationObserver !== null) {
		mutationObserver.disconnect();
	}
});
</script>
