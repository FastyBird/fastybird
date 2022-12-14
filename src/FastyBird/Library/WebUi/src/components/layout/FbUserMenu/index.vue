<template>
	<div
		ref="container"
		class="fb-theme-layout-user-menu__container"
	>
		<div
			class="fb-theme-layout-user-menu__button"
			@click.prevent="toggle"
		>
			<div class="fb-theme-layout-user-menu__button-avatar">
				<div class="fb-theme-layout-user-menu__button-avatar-inner">
					<slot
						v-if="'avatar' in $slots"
						name="avatar"
					/>

					<svg
						v-else
						xmlns="http://www.w3.org/2000/svg"
						viewBox="0 0 3.80175 2.74985"
					>
						<path
							d="M0.187767 1.69877c-0.103706,0 -0.187767,-0.0840617 -0.187767,-0.18774 0,-0.103678 0.0840617,-0.187767 0.187767,-0.187767 0.262409,0 0.522518,-0.00293689 0.785231,-0.0021057 0.0406178,-0.267479 0.166655,-0.506974 0.34938,-0.689671 0.202064,-0.202092 0.473588,-0.334834 0.775645,-0.359354 -0.0341345,-0.0668559 -0.0258502,-0.150557 0.0271524,-0.209822 0.0688508,-0.0769411 0.187102,-0.0835075 0.264043,-0.0146568l0.424631 0.379774 0.00784095 0.00748077c0.0936481,0.0543325 0.179483,0.120551 0.255537,0.196578 0.0810693,0.0810416 0.151001,0.173304 0.207217,0.274212l0.426986 0.396758c0.0413105,0.0337189 0.0757497,0.0894921 0.0903233,0.138339l-0.4581 0c-0.0533905,0 -0.0973331,0.0439149 -0.0973331,0.0973054l0 8.31196e-005c0,0.0533628 0.0439703,0.0973331 0.0973331,0.0973331 0.148812,0 0.297596,0 0.446408,0 -0.0147399,0.0339405 -0.0411442,0.0736717 -0.0694326,0.0978041l-0.440202 0.409059c-0.00809031,0.00750847 -0.0165962,0.0141026 -0.0255454,0.0199487 -0.0505367,0.0814295 -0.110217,0.15657 -0.177654,0.224007 -0.224506,0.224562 -0.534764,0.36351 -0.877439,0.36351 -0.320565,0 -0.636004,-0.171005 -0.928945,-0.391216l0.413797 0c0.182143,0 0.32993,-0.147759 0.32993,-0.32993 0,-0.182143 -0.147787,-0.32993 -0.32993,-0.32993 -0.498967,0 -0.997934,0 -1.49687,0zm2.30261 0.625531c0.122075,-0.0434993 0.231571,-0.113652 0.321368,-0.203449 0.156597,-0.156542 0.253459,-0.372958 0.253459,-0.611927 0,-0.238969 -0.0968621,-0.455385 -0.253459,-0.611954 -0.15657,-0.15657 -0.372958,-0.253459 -0.611954,-0.253459 -0.238969,0 -0.455385,0.0968898 -0.611927,0.253459 -0.114594,0.114622 -0.197215,0.2613 -0.233261,0.425406 0.130719,0.000387892 0.2613,0.000886609 0.39202,0.000886609 0.357193,0 0.61032,0.204918 0.724803,0.4517 0.0790191,0.170506 0.0946178,0.374897 0.0189513,0.549338zm0.348133 -1.02207c0,-0.113846 -0.0922905,-0.206164 -0.206164,-0.206164 -0.113846,0 -0.206137,0.0923182 -0.206137,0.206164 0,0.113846 0.0922905,0.206164 0.206137,0.206164 0.113874,0 0.206164,-0.0923182 0.206164,-0.206164zm-1.15387 0.538726c0.103678,0 0.18774,0.0840617 0.18774,0.18774 0,0.103678 -0.0840617,0.187767 -0.18774,0.187767l-1.02043 0c-0.103678,0 -0.187767,-0.0840894 -0.187767,-0.187767 0,-0.103678 0.0840894,-0.18774 0.187767,-0.18774l1.02043 0z"
						/>
					</svg>
				</div>
			</div>

			<div class="fb-theme-layout-user-menu__button-name">
				{{ name }}
				<span class="fb-theme-layout-user-menu__button-caret" />
			</div>
		</div>

		<ul
			v-if="'items' in $slots"
			ref="userNavigation"
			:data-collapsed="collapsed"
			tabindex="0"
			@keydown.esc="blur"
		>
			<slot name="items" />
		</ul>
	</div>
</template>

<script lang="ts">
import { defineComponent, nextTick, PropType, ref, watch } from 'vue';

import { onClickOutside } from '@vueuse/core';

export default defineComponent({
	name: 'FbLayoutUserMenu',

	props: {
		name: {
			type: String as PropType<string>,
			default: null,
		},
	},

	setup() {
		const container = ref<HTMLElement | null>(null);
		const collapsed = ref<boolean>(true);
		const userNavigation = ref<HTMLElement | null>(null);

		const blur = (): void => {
			collapsed.value = true;
		};

		const toggle = (): void => {
			collapsed.value = !collapsed.value;
		};

		onClickOutside(container, () => blur());

		watch(
			(): boolean => collapsed.value,
			(val): void => {
				if (!val) {
					nextTick(() => {
						if (userNavigation.value !== null) {
							userNavigation.value.focus();
						}
					});
				}
			}
		);

		return {
			container,
			collapsed,
			userNavigation,
			blur,
			toggle,
		};
	},
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
