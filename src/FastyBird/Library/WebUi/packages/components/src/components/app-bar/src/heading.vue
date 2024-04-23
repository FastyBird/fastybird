<template>
	<template v-if="props.teleport">
		<teleport :to="`#${teleportTarget}`">
			<div
				v-if="'prepend' in $slots"
				:class="[ns.e('prepend')]"
			>
				<slot name="prepend" />
			</div>

			<h1 :class="[ns.e('title'), ns.em('title', `align-${props.align}`)]">
				<template v-if="'subtitle' in $slots">
					<span><slot name="title" /></span>
					<small><slot name="subtitle" /></small>
				</template>
				<template v-else>
					<slot name="title" />
				</template>
			</h1>

			<div
				v-if="'append' in $slots"
				:class="[ns.e('append')]"
			>
				<slot name="append" />
			</div>
		</teleport>
	</template>

	<template v-else>
		<div
			v-if="'prepend' in $slots"
			:class="[ns.e('prepend')]"
		>
			<slot name="prepend" />
		</div>

		<h1 :class="[ns.e('title'), ns.em('title', `align-${props.align}`)]">
			<template v-if="'subtitle' in $slots">
				<span><slot name="title" /></span>
				<small><slot name="subtitle" /></small>
			</template>
			<template v-else>
				<slot name="title" />
			</template>
		</h1>

		<div
			v-if="'append' in $slots"
			:class="[ns.e('append')]"
		>
			<slot name="append" />
		</div>
	</template>
</template>

<script lang="ts" setup>
import { appBarHeadingProps } from './heading';

import { useNamespace } from '@fastybird/web-ui-hooks';

defineOptions({
	name: 'FbAppBarHeading',
});

const props = defineProps(appBarHeadingProps);

const ns = useNamespace('app-bar-heading');

const teleportTarget = 'fb-app-bar-heading';
</script>
