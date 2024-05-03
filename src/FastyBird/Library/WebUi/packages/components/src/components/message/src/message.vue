<template>
	<transition :name="ns.b('fade')" @before-leave="$emit('close')" @after-leave="$emit('destroy')">
		<div
			v-show="visible"
			:id="props.id"
			ref="messageRef"
			:class="[
				ns.b(),
				{ [ns.m('type-' + props.type)]: props.type },
				ns.is('center', props.center),
				ns.is('closable', props.showClose),
				props.customClass,
			]"
			:style="customStyle"
			role="alert"
			@mouseenter="handleClearTimer"
			@mouseleave="handleStartTimer"
		>
			<fb-badge v-if="props.repeatNum > 1" :value="props.repeatNum" :type="badgeType" :class="ns.e('badge')" />
			<fb-icon v-if="iconComponent" :class="[ns.e('icon'), typeClass]">
				<component :is="iconComponent" />
			</fb-icon>
			<slot>
				<p v-if="!props.dangerouslyUseHTMLString" :class="ns.e('content')">
					{{ props.message }}
				</p>
				<!-- Caution here, message could've been compromised, never use user's input as message -->
				<p v-else :class="ns.e('content')" v-html="props.message" />
			</slot>
			<fb-icon v-if="props.showClose" :class="ns.e('closeBtn')" @click.stop="handleClose">
				<close />
			</fb-icon>
		</div>
	</transition>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, watch } from 'vue';

import { useEventListener, useResizeObserver, useTimeoutFn } from '@vueuse/core';
import { CloseComponentsMap, TypeComponentsMap } from '@fastybird/web-ui-utils';
import { EVENT_CODE, VariantTypes } from '@fastybird/web-ui-constants';
import { useNamespace } from '@fastybird/web-ui-hooks';

import FbBadge from '../../badge';
import { useGlobalComponentSettings } from '../../config-provider';
import { FbIcon } from '../../icon';
import { messageEmits, messageProps } from './message';
import { getLastOffset, getOffsetOrSpace } from './instance';

import type { BadgeProps } from '../../badge';
import type { CSSProperties } from 'vue';

defineOptions({
	name: 'FbMessage',
});

const props = defineProps(messageProps);
defineEmits(messageEmits);

const { zIndex } = useGlobalComponentSettings();
const ns = useNamespace('message');
const { currentZIndex, nextZIndex } = zIndex;
const { close } = CloseComponentsMap;

const messageRef = ref<HTMLDivElement>();
const visible = ref<boolean>(false);
const height = ref<number>(0);

let stopTimer: (() => void) | undefined = undefined;

const badgeType = computed<BadgeProps['variant']>(() =>
	props.type ? (props.type === VariantTypes.ERROR ? VariantTypes.DANGER : props.type) : VariantTypes.INFO
);

const typeClass = computed<string[]>((): string[] => {
	return [ns.em('icon', props.type)];
});

const iconComponent = computed(() => props.icon || TypeComponentsMap[props.type as 'success' | 'warning' | 'error' | 'info'] || '');

const lastOffset = computed<number>((): number => getLastOffset(props.id));

const offset = computed<number>((): number => (getOffsetOrSpace(props.id, props.offset) || 0) + lastOffset.value);

const bottom = computed<number>((): number => height.value + offset.value);

const customStyle = computed<CSSProperties>(
	(): CSSProperties => ({
		top: `${offset.value}px`,
		zIndex: currentZIndex.value,
	})
);

onMounted((): void => {
	handleStartTimer();
	nextZIndex();

	visible.value = true;
});

watch(
	(): number => props.repeatNum,
	() => {
		handleClearTimer();
		handleStartTimer();
	}
);

const handleStartTimer = (): void => {
	if (props.duration === 0) {
		return;
	}

	({ stop: stopTimer } = useTimeoutFn(() => {
		handleClose();
	}, props.duration));
};

const handleClearTimer = (): void => {
	stopTimer?.();
};

const handleClose = (): void => {
	visible.value = false;
};

const handleKeydown = ({ code }: KeyboardEvent): void => {
	if (code === EVENT_CODE.esc) {
		// press esc to close the message
		handleClose();
	}
};

useEventListener(document, 'keydown', handleKeydown);

useResizeObserver(messageRef, (): void => {
	height.value = messageRef.value!.getBoundingClientRect().height;
});

defineExpose({
	/** @description */
	visible,
	/** @description */
	bottom,
	/** @description */
	close: handleClose,
});
</script>
