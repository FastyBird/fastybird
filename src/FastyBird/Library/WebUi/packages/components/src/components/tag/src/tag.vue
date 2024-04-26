<template>
	<span v-if="props.disableTransitions" :class="containerClass" :style="{ backgroundColor: props.color }" @click="handleClick">
		<span :class="ns.e('content')">
			<slot />
		</span>
		<fb-icon v-if="props.closable" :class="ns.e('close')" @click.stop="handleClose">
			<close />
		</fb-icon>
	</span>

	<transition v-else :name="`${ns.namespace.value}-zoom-in-center`" appear>
		<span :class="containerClass" :style="{ backgroundColor: props.color }" @click="handleClick">
			<span :class="ns.e('content')">
				<slot />
			</span>
			<fb-icon v-if="props.closable" :class="ns.e('close')" @click.stop="handleClose">
				<close />
			</fb-icon>
		</span>
	</transition>
</template>

<script lang="ts" setup>
import { computed } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';
import { CloseComponentsMap } from '@fastybird/web-ui-utils';
import { ComponentSizeTypes, VariantTypes } from '@fastybird/web-ui-constants';

import { useFormSize } from '../../form';
import { FbIcon } from '../../icon';
import { tagEmits, tagProps } from './tag';

defineOptions({
	name: 'FbTag',
});

const props = defineProps(tagProps);
const emit = defineEmits(tagEmits);
const { close } = CloseComponentsMap;

const tagSize = useFormSize();
const ns = useNamespace('tag');

const containerClass = computed(() => {
	const { variant, hit, effect, closable, round } = props;

	return [
		ns.b(),
		ns.is('closable', closable),
		ns.m('variant-' + variant || VariantTypes.PRIMARY),
		ns.m('size-' + tagSize.value || ComponentSizeTypes.DEFAULT),
		ns.m(effect),
		ns.is('hit', hit),
		ns.is('round', round),
	];
});

// methods
const handleClose = (event: UIEvent): void => {
	emit('close', event);
};

const handleClick = (event: UIEvent): void => {
	emit('click', event);
};
</script>
