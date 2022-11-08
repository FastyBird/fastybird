<template>
	<div class="fb-theme-layout-footer__container">
		<div class="fb-theme-layout-footer__inner">
			<template v-if="'default' in $slots">
				<slot />
			</template>

			<template v-else>
				{{ copyright }}
				<a
					:href="website"
					target="_blank"
					rel="noreferrer"
				>
					{{ author }}
				</a>
			</template>
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';

export default defineComponent({
	name: 'FbLayoutFooter',

	props: {
		copyright: {
			type: String as PropType<string>,
			default: '© 2017',
		},

		author: {
			type: String as PropType<string>,
			default: 'FastyBird s.r.o.',
		},

		website: {
			type: String as PropType<string>,
			default: 'https://www.fastybird.com',
		},
	},
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
