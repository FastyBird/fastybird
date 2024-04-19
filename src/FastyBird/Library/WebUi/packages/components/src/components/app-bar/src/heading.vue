<template>
	<template v-if="props.teleport">
		<teleport to="#fb-layout-header-heading">
			<div class="fb-theme-layout-phone-header-heading__container">
				<div :class="['fb-theme-layout-phone-header-heading__heading', `fb-theme-layout-phone-header-heading__heading-${props.align}`]">
					<h1 v-if="!props.subHeading">
						{{ props.heading }}
					</h1>

					<h1 v-if="props.subHeading">
						<span>{{ props.heading }}</span>
						<small>{{ props.subHeading }}</small>
					</h1>
				</div>

				<div
					v-if="'icon' in $slots"
					class="fb-theme-layout-phone-header-heading__icon"
				>
					<slot name="icon" />
				</div>
			</div>
		</teleport>
	</template>

	<template v-else>
		<div class="fb-theme-layout-phone-header-heading__container">
			<div :class="['fb-theme-layout-phone-header-heading__heading', `fb-theme-layout-phone-header-heading__heading-${props.align}`]">
				<h1 v-if="!props.subHeading">
					{{ props.heading }}
				</h1>

				<h1 v-if="props.subHeading">
					<span>{{ props.heading }}</span>
					<small>{{ props.subHeading }}</small>
				</h1>
			</div>

			<div
				v-if="'icon' in $slots"
				class="fb-theme-layout-phone-header-heading__icon"
			>
				<slot name="icon" />
			</div>
		</div>
	</template>
</template>

<script lang="ts" setup>
import { appBarHeadingProps } from './heading';

defineOptions({
	name: 'FbAppBarHeading',
});

const props = defineProps(appBarHeadingProps);
</script>
