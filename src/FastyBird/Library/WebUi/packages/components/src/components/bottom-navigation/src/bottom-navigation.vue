<template>
	<div
		ref="element"
		class="fb-theme-layout-phone-menu__container"
		@keyup.esc="emit('close', $event)"
	>
		<transition name="fb-theme-layout-phone-menu-backdrop">
			<div
				v-if="show || teleportHasContent"
				class="fb-theme-layout-phone-menu__backdrop"
				@click.prevent="emit('close', $event)"
			/>
		</transition>

		<transition name="fb-theme-layout-phone-menu-content">
			<div
				v-if="props.show || teleportHasContent"
				class="fb-theme-layout-phone-menu__inner"
			>
				<div class="fb-theme-layout-phone-menu__content">
					<h4
						id="fb-layout-phone-menu-heading"
						class="fb-theme-layout-phone-menu__heading"
					>
						<slot name="heading" />
					</h4>

					<div
						id="fb-layout-phone-menu-items"
						@change="itemsTeleportChanged"
					>
						<slot />
					</div>
				</div>

				<div
					v-if="props.showClose"
					id="fb-layout-phone-menu-button"
					class="fb-theme-layout-phone-menu__footer"
				>
					<slot name="close-button">
						<fb-button
							:size="ComponentSizeTypes.LARGE"
							link
							block
							@click.prevent="emit('close', $event)"
						>
							{{ props.closeBtnText }}
						</fb-button>
					</slot>
				</div>
			</div>
		</transition>
	</div>
</template>

<script lang="ts" setup>
import { nextTick, ref, watch } from 'vue';

import { ComponentSizeTypes } from '@fastybird/web-ui-constants';

import { FbButton } from '../../button';
import { bottomNavigationEmits, bottomNavigationProps } from './bottom-navigation';

defineOptions({
	name: 'FbBottomNavigation',
});

const props = defineProps(bottomNavigationProps);
const emit = defineEmits(bottomNavigationEmits);

const element = ref<HTMLElement | null>(null);

const teleportHasContent = ref<boolean>(false);

const itemsTeleportChanged = (payload: Event): void => {
	teleportHasContent.value = payload.target !== null;
};

watch(
	(): boolean => teleportHasContent.value,
	(val: boolean): void => {
		nextTick((): void => {
			if (val && element.value !== null) {
				element.value.focus();
			}
		});
	}
);
</script>
