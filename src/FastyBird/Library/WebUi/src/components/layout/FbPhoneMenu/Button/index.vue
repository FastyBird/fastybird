<template>
	<teleport
		to="#fb-layout-phone-menu-button"
		:disabled="!teleport"
	>
		<slot />
	</teleport>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';

export default defineComponent({
	name: 'FbLayoutPhoneMenuButton',

	props: {
		closeBtnText: {
			type: String as PropType<string>,
			default: 'Close',
		},

		teleport: {
			type: Boolean as PropType<boolean>,
			default: true,
		},
	},
});
</script>
