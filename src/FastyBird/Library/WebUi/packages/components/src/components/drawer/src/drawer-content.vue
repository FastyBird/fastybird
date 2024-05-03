<template>
	<div :ref="composedDrawerRef" :class="drawerClass" :style="isHorizontal ? 'width: ' + drawerSize : 'height: ' + drawerSize" tabindex="-1">
		<slot name="content">
			<fb-drawer-header
				v-if="props.showHeader"
				:variant="props.variant"
				:title="props.title"
				:subtitle="props.subtitle"
				:icon="props.icon"
				:close-icon="props.closeIcon"
				:closable="props.closable"
				:aria-level="props.ariaLevel"
				@close="$emit('close', $event)"
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
			</fb-drawer-header>

			<div :id="bodyId" :class="ns.e('body')">
				<slot />
			</div>

			<fb-drawer-footer
				v-if="props.showFooter"
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
			</fb-drawer-footer>
		</slot>
	</div>
</template>

<script lang="ts" setup>
import { computed, inject } from 'vue';

import { addUnit, composeRefs } from '@fastybird/web-ui-utils';

import { FOCUS_TRAP_INJECTION_KEY } from '../../focus-trap';
import { drawerContextKey } from './constants';
import { drawerContentEmits, drawerContentProps } from './drawer-content';
import FbDrawerFooter from './drawer-footer.vue';
import FbDrawerHeader from './drawer-header.vue';

defineOptions({
	name: 'FbDrawerContent',
});

const props = defineProps(drawerContentProps);

defineEmits(drawerContentEmits);

const { drawerRef, bodyId, ns } = inject(drawerContextKey)!;
const { focusTrapRef } = inject(FOCUS_TRAP_INJECTION_KEY)!;

const drawerClass = computed<(string | { [x: string]: boolean })[]>((): (string | { [x: string]: boolean })[] => [
	ns.b(),
	ns.m(props.direction),
	{ [ns.is('visible')]: props.visible },
]);

const composedDrawerRef = composeRefs(focusTrapRef, drawerRef);

const isHorizontal = computed<boolean>((): boolean => props.direction === 'rtl' || props.direction === 'ltr');
const drawerSize = computed<string | undefined>((): string | undefined => addUnit(props.size));
</script>
