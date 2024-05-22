<template>
	<div
		ref="element"
		class="fb-bottom-menu__container"
		@keyup.esc="emit('close', $event)"
	>
		<transition name="fb-bottom-menu-backdrop">
			<div
				v-if="show || teleportHasContent"
				class="fb-bottom-menu__backdrop"
				@click.prevent="emit('close', $event)"
			/>
		</transition>

		<transition name="fb-bottom-menu-content">
			<div
				v-if="props.show || teleportHasContent"
				class="fb-bottom-menu__inner"
			>
				<div class="fb-bottom-menu__content">
					<h4
						id="fb-bottom-menu-heading"
						class="fb-bottom-menu__heading"
					>
						<slot name="heading" />
					</h4>

					<div
						id="fb-bottom-menu-items"
						@change="itemsTeleportChanged"
					>
						<slot />
					</div>
				</div>

				<div
					v-if="props.showClose"
					id="fb-bottom-menu-button"
					class="fb-bottom-menu__footer"
				>
					<slot name="close-button">
						<el-button
							size="large"
							link
							@click.prevent="emit('close', $event)"
						>
							{{ props.closeBtnText }}
						</el-button>
					</slot>
				</div>
			</div>
		</transition>
	</div>
</template>

<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import { ElButton } from 'element-plus';

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
