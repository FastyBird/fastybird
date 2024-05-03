<template>
	<div :ref="composedModalRef" :class="modalClass" :style="style" tabindex="-1">
		<slot name="content">
			<fb-modal-header
				v-if="props.showHeader"
				:layout="props.layout"
				:variant="props.variant"
				:title="props.title"
				:subtitle="props.subtitle"
				:icon="props.icon"
				:show-left-btn="props.showLeftBtn"
				:left-btn-label="props.leftBtnLabel"
				:show-right-btn="props.showRightBtn"
				:right-btn-label="props.rightBtnLabel"
				:show-close="props.showClose"
				:close-icon="props.closeIcon"
				:closable="props.closable"
				:aria-level="props.ariaLevel"
				@close="$emit('close', $event)"
				@left-click="$emit('leftClick', $event)"
				@right-click="$emit('rightClick', $event)"
			>
				<template v-if="'title' in $slots" #title>
					<slot name="title" />
				</template>

				<template v-if="'subtitle' in $slots" #subtitle>
					<slot name="subtitle" />
				</template>

				<template v-if="'icon' in $slots" #icon>
					<slot name="icon" />
				</template>

				<template v-if="'description' in $slots" #description>
					<slot name="description" />
				</template>

				<template v-if="'left-button' in $slots" #left-button>
					<slot name="left-button" />
				</template>

				<template v-if="'right-button' in $slots" #right-button>
					<slot name="right-button" />
				</template>

				<template v-if="'header' in $slots" #default>
					<slot name="header" />
				</template>
			</fb-modal-header>

			<div :id="bodyId" :class="ns.e('body')">
				<slot />
			</div>

			<fb-modal-footer
				v-if="props.showFooter"
				:layout="props.layout"
				:variant="props.variant"
				:show-left-btn="props.showLeftBtn"
				:left-btn-label="props.leftBtnLabel"
				:show-right-btn="props.showRightBtn"
				:right-btn-label="props.rightBtnLabel"
				@left-click="$emit('leftClick', $event)"
				@right-click="$emit('rightClick', $event)"
			>
				<template v-if="'left-button' in $slots" #left-button>
					<slot name="left-button" />
				</template>

				<template v-if="'right-button' in $slots" #right-button>
					<slot name="right-button" />
				</template>

				<template v-if="'footer' in $slots" #default>
					<slot name="footer" />
				</template>
			</fb-modal-footer>
		</slot>
	</div>
</template>

<script lang="ts" setup>
import { computed, inject } from 'vue';

import { useDraggable } from '@fastybird/web-ui-hooks';
import { composeRefs } from '@fastybird/web-ui-utils';

import { FOCUS_TRAP_INJECTION_KEY } from '../../focus-trap';
import { modalContextKey } from './constants';
import { modalContentEmits, modalContentProps } from './modal-content';
import FbModalFooter from './modal-footer.vue';
import FbModalHeader from './modal-header.vue';

defineOptions({
	name: 'FbModalContent',
});

const props = defineProps(modalContentProps);

defineEmits(modalContentEmits);

const { modalRef, headerRef, bodyId, ns, style } = inject(modalContextKey)!;
const { focusTrapRef } = inject(FOCUS_TRAP_INJECTION_KEY)!;

const modalClass = computed<(string | { [x: string]: boolean })[]>((): (string | { [x: string]: boolean })[] => [
	ns.b(),
	ns.is('fullscreen', props.fullscreen),
	ns.is('draggable', props.draggable),
	ns.is('align-center', props.alignCenter),
	{ [ns.m('center')]: props.center },
	ns.m('layout-' + props.layout),
]);

const composedModalRef = composeRefs(focusTrapRef, modalRef);

const draggable = computed<boolean>((): boolean => props.draggable);
const overflow = computed<boolean>((): boolean => props.overflow);

useDraggable(modalRef, headerRef, draggable, overflow);
</script>
