<template>
	<teleport to="body" :disabled="!props.appendToBody">
		<transition :name="ns.b('fade')" @after-enter="afterEnter" @after-leave="afterLeave" @before-leave="beforeLeave">
			<fb-overlay v-show="visible" :mask="props.backdrop" :overlay-class="props.customClass" :z-index="zIndex" @click="onBackdropClick">
				<fb-focus-trap
					loop
					:trapped="visible"
					:focus-start-el="focusStartRef"
					@focus-after-trapped="onOpenAutoFocus"
					@focus-after-released="onCloseAutoFocus"
					@focusout-prevented="onFocusoutPrevented"
					@release-requested="onCloseRequested"
				>
					<fb-drawer-content
						v-if="rendered"
						ref="drawerRef"
						:direction="props.direction"
						:size="props.size"
						:variant="props.variant"
						:visible="props.visible"
						:title="props.title"
						:subtitle="props.subtitle"
						:icon="props.icon"
						:show-header="props.showHeader"
						:show-footer="props.showFooter"
						:show-left-btn="props.showLeftBtn"
						:left-btn-label="props.leftBtnLabel"
						:show-right-btn="props.showRightBtn"
						:right-btn-label="props.rightBtnLabel"
						:close-icon="props.closeIcon"
						:aria-level="props.ariaLevel"
						:closable="props.closable"
						@close="handleClose"
						@left-click="emit('leftClick')"
						@right-click="emit('rightClick')"
					>
						<template v-if="'content' in $slots" #content>
							<slot name="content" />
						</template>

						<template v-if="'header' in $slots" #header>
							<slot name="header" :close="handleClose" :title-id="titleId" :title-class="ns.be('header', 'title')" />
						</template>

						<template v-if="'footer' in $slots" #footer>
							<slot name="footer" />
						</template>

						<template v-if="'title' in $slots || props.title" #title>
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

						<slot />
					</fb-drawer-content>
				</fb-focus-trap>
			</fb-overlay>
		</transition>
	</teleport>
</template>

<script lang="ts" setup>
import { provide, ref } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';

import { FbFocusTrap } from '../../focus-trap';
import { FbOverlay } from '../../overlay';
import { drawerContextKey } from './constants';
import { drawerEmits, drawerProps } from './drawer';
import FbDrawerContent from './drawer-content.vue';
import { useDrawer } from './hooks';

defineOptions({
	name: 'FbDrawer',
	inheritAttrs: false,
});

const props = defineProps(drawerProps);
const emit = defineEmits(drawerEmits);

const ns = useNamespace('drawer');

const drawerRef = ref<HTMLElement | undefined>();
const headerRef = ref<HTMLElement | undefined>();
const footerRef = ref<HTMLElement | undefined>();
const focusStartRef = ref<HTMLElement | undefined>();

const {
	afterEnter,
	afterLeave,
	beforeLeave,
	handleClose,
	onBackdropClick,
	onOpenAutoFocus,
	onCloseAutoFocus,
	onCloseRequested,
	onFocusoutPrevented,
	titleId,
	bodyId,
	rendered,
	visible,
	zIndex,
} = useDrawer(props, drawerRef);

provide(drawerContextKey, {
	drawerRef,
	headerRef,
	footerRef,
	bodyId,
	ns,
	rendered,
});

defineExpose({
	handleClose,
	afterEnter,
	afterLeave,
});
</script>
