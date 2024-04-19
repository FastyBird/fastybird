<template>
	<teleport
		:to="props.appendTo"
		:disabled="props.appendTo !== 'body' ? false : !props.appendToBody"
	>
		<transition
			name="modal-fade"
			@after-enter="afterEnter"
			@after-leave="afterLeave"
			@before-leave="beforeLeave"
		>
			<fb-overlay
				v-show="visible"
				custom-mask-event
				:mask="props.backdrop"
				:overlay-class="props.customClass"
				:z-index="zIndex"
			>
				<div
					role="dialog"
					aria-modal="true"
					:aria-label="props.title || undefined"
					:aria-labelledby="!props.title ? titleId : undefined"
					:aria-describedby="bodyId"
					:class="`${ns.namespace.value}-overlay-modal`"
					:style="overlayModalStyle"
					@click="overlayEvent.onClick"
					@mousedown="overlayEvent.onMousedown"
					@mouseup="overlayEvent.onMouseup"
				>
					<fb-focus-trap
						loop
						:trapped="visible"
						focus-start-el="container"
						@focus-after-trapped="onOpenAutoFocus"
						@focus-after-released="onCloseAutoFocus"
						@focusout-prevented="onFocusoutPrevented"
						@release-requested="onCloseRequested"
					>
						<fb-modal-content
							v-if="rendered"
							ref="modalContentRef"
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
							:aria-level="props.ariaLevel"
							:center="props.center"
							:align-center="props.alignCenter"
							:draggable="draggable"
							:overflow="props.overflow"
							:closable="props.closable"
							:fullscreen="props.fullscreen"
							@close="handleClose"
							@leftClick="$emit('leftClick')"
							@rightClick="$emit('rightClick')"
						>
							<template
								v-if="'content' in $slots"
								#content
							>
								<slot name="content" />
							</template>

							<template
								v-if="'header' in $slots"
								#header
							>
								<slot
									name="header"
									:close="handleClose"
									:title-id="titleId"
									:title-class="ns.be('header', 'title')"
								/>
							</template>

							<template
								v-if="'footer' in $slots"
								#footer
							>
								<slot name="footer" />
							</template>

							<template
								v-if="'title' in $slots || props.title"
								#title
							>
								<slot name="title" />
							</template>

							<template
								v-if="'subtitle' in $slots"
								#subtitle
							>
								<slot name="subtitle" />
							</template>

							<template
								v-if="'icon' in $slots"
								#icon
							>
								<slot name="icon" />
							</template>

							<template
								v-if="'description' in $slots"
								#description
							>
								<slot name="description" />
							</template>

							<template
								v-if="'left-button' in $slots"
								#left-button
							>
								<slot name="left-button" />
							</template>

							<template
								v-if="'right-button' in $slots"
								#right-button
							>
								<slot name="right-button" />
							</template>

							<slot />
						</fb-modal-content>
					</fb-focus-trap>
				</div>
			</fb-overlay>
		</transition>
	</teleport>
</template>

<script lang="ts" setup>
import { computed, provide, ref } from 'vue';

import { useNamespace, useSameTarget } from '@fastybird/web-ui-hooks';

import { FbFocusTrap } from '../../focus-trap';
import { FbOverlay } from '../../overlay';
import FbModalContent from './modal-content.vue';
import { modalContextKey } from './constants';
import { modalEmits, modalProps } from './modal';
import { useModal } from './hooks';

defineOptions({
	name: 'FbModal',
	inheritAttrs: false,
});

const props = defineProps(modalProps);
defineEmits(modalEmits);

const ns = useNamespace('modal');
const modalRef = ref<HTMLElement | undefined>();
const headerRef = ref<HTMLElement | undefined>();
const footerRef = ref<HTMLElement | undefined>();
const modalContentRef = ref();

const {
	visible,
	titleId,
	bodyId,
	style,
	overlayModalStyle,
	rendered,
	zIndex,
	afterEnter,
	afterLeave,
	beforeLeave,
	handleClose,
	onBackdropClick,
	onOpenAutoFocus,
	onCloseAutoFocus,
	onCloseRequested,
	onFocusoutPrevented,
} = useModal(props, modalRef);

provide(modalContextKey, {
	modalRef,
	headerRef,
	footerRef,
	bodyId,
	ns,
	rendered,
	style,
});

const overlayEvent = useSameTarget(onBackdropClick);

const draggable = computed(() => props.draggable && !props.fullscreen);

defineExpose({
	/** @description whether the modal is visible */
	visible,
	/** @description modal content html element */
	modalContentRef,
});
</script>
