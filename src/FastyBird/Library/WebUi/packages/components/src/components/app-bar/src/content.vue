<template>
	<template v-if="props.teleport">
		<teleport to="#fb-layout-header-sub-content">
			<div class="fb-theme-layout-phone-header-content__container">
				<slot />
			</div>
		</teleport>
	</template>

	<template v-else>
		<div class="fb-theme-layout-phone-header-content__container">
			<slot />
		</div>
	</template>
</template>

<script lang="ts" setup>
import { appBarContentProps } from './content';

defineOptions({
	name: 'FbAppBarContent',
});

const props = defineProps(appBarContentProps);
</script>
