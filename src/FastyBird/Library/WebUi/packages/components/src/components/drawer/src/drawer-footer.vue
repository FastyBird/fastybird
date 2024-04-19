<template>
	<footer
		ref="footerRef"
		:class="[ns.b(), ns.m('variant-' + props.variant)]"
	>
		<slot>
			<div
				v-if="props.showLeftBtn"
				:class="ns.e('left-button')"
			>
				<slot name="left-button">
					<fb-button
						:variant="VariantTypes.DEFAULT"
						:size="ComponentSizeTypes.LARGE"
						text
						uppercase
						@click.prevent="emit('leftClick', $event)"
					>
						{{ props.leftBtnLabel ?? t('component.drawer.leftButton') }}
					</fb-button>
				</slot>
			</div>

			<div
				v-if="props.showRightBtn"
				:class="ns.e('right-button')"
			>
				<slot name="right-button">
					<fb-button
						:variant="props.variant"
						:size="ComponentSizeTypes.LARGE"
						plain
						uppercase
						@click.prevent="emit('rightClick', $event)"
					>
						{{ props.rightBtnLabel ?? t('component.drawer.rightButton') }}
					</fb-button>
				</slot>
			</div>
		</slot>
	</footer>
</template>

<script lang="ts" setup>
import { inject } from 'vue';

import { useLocale, useNamespace } from '@fastybird/web-ui-hooks';
import { ComponentSizeTypes, VariantTypes } from '@fastybird/web-ui-constants';

import { FbButton } from '../../button';
import { drawerContextKey } from './constants';
import { drawerFooterProps, drawerFooterEmits } from './drawer-footer';

defineOptions({
	name: 'FbDrawerFooter',
});

const props = defineProps(drawerFooterProps);
const emit = defineEmits(drawerFooterEmits);

const { t } = useLocale();

const ns = useNamespace('drawer-footer');

const { footerRef } = inject(drawerContextKey)!;
</script>
