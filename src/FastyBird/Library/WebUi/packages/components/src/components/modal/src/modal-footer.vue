<template>
	<footer v-if="![LayoutTypes.PHONE, LayoutTypes.TABLET].includes(props.layout)" ref="footerRef" :class="ns.b()">
		<slot>
			<div v-if="props.showLeftBtn" :class="ns.e('left-button')">
				<slot name="left-button">
					<fb-button :variant="VariantTypes.DEFAULT" :size="ComponentSizeTypes.LARGE" text uppercase @click.prevent="$emit('leftClick', $event)">
						{{ props.leftBtnLabel ?? t('component.modal.leftButton') }}
					</fb-button>
				</slot>
			</div>

			<div v-if="props.showRightBtn" :class="ns.e('right-button')">
				<slot name="right-button">
					<fb-button :variant="props.variant" :size="ComponentSizeTypes.LARGE" plain uppercase @click.prevent="$emit('rightClick', $event)">
						{{ props.rightBtnLabel ?? t('component.modal.rightButton') }}
					</fb-button>
				</slot>
			</div>
		</slot>
	</footer>
</template>

<script lang="ts" setup>
import { inject } from 'vue';

import { useLocale, useNamespace } from '@fastybird/web-ui-hooks';
import { ComponentSizeTypes, LayoutTypes, VariantTypes } from '@fastybird/web-ui-constants';

import { FbButton } from '../../button';
import { modalContextKey } from './constants';
import { modalFooterProps, modalFooterEmits } from './modal-footer';

const { t } = useLocale();
const ns = useNamespace('modal-footer');

defineOptions({
	name: 'FbModalFooter',
});

const props = defineProps(modalFooterProps);

defineEmits(modalFooterEmits);

const { footerRef } = inject(modalContextKey)!;
</script>
