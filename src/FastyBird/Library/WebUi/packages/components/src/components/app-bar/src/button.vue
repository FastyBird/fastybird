<template>
	<template v-if="teleport">
		<teleport :to="`#${teleportTarget}`">
			<fb-button
				v-bind="props"
				:variant="VariantTypes.PRIMARY"
				:size="props.small ? ComponentSizeTypes.SMALL : ComponentSizeTypes.LARGE"
				:circle="'icon' in $slots"
				:class="[ns.b(), ns.m(`align-${props.align}`)]"
				@click="emit('click', $event)"
			>
				<template v-if="'icon' in $slots">
					<slot name="icon" />
				</template>

				<slot />
			</fb-button>
		</teleport>
	</template>

	<template v-else>
		<fb-button
			v-bind="props"
			:variant="VariantTypes.PRIMARY"
			:size="props.small ? ComponentSizeTypes.SMALL : ComponentSizeTypes.LARGE"
			:circle="'icon' in $slots"
			:class="[ns.b(), ns.m(`align-${props.align}`)]"
			@click="emit('click', $event)"
		>
			<template v-if="'icon' in $slots">
				<slot name="icon" />
			</template>

			<slot />
		</fb-button>
	</template>
</template>

<script lang="ts" setup>
import { ComponentSizeTypes, VariantTypes } from '@fastybird/web-ui-constants';
import { useNamespace } from '@fastybird/web-ui-hooks';

import { FbButton } from '../../button';
import { appBarButtonEmits, appBarButtonProps } from './button';

defineOptions({
	name: 'FbAppBarButton',
});

const props = defineProps(appBarButtonProps);
const emit = defineEmits(appBarButtonEmits);

const ns = useNamespace('app-bar-button');

let teleportTarget = 'fb-app-bar-button';

if (props.small) {
	teleportTarget = `${teleportTarget}-small`;
} else {
	teleportTarget = `${teleportTarget}-${props.align}`;
}
</script>
