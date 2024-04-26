<template>
	<transition :name="ns.b('fade')">
		<div
			v-show="visible"
			:class="[
				ns.b(),
				ns.m('variant-' + props.variant ?? VariantTypes.DEFAULT),
				ns.is('center', props.center),
				ns.is(props.effect ?? EffectTypes.LIGHT),
			]"
			role="alert"
		>
			<div v-if="'icon' in $slots || (props.icon && icon)" :class="iconClass">
				<slot name="icon">
					<fb-icon>
						<component :is="icon" />
					</fb-icon>
				</slot>
			</div>

			<div :class="ns.e('content')">
				<template v-if="'default' in $slots">
					<slot />
				</template>

				<template v-else>
					<span v-if="'title' in $slots" :class="titleClass">
						<slot name="title" />
					</span>

					<p v-if="'description' in $slots" :class="ns.e('description')">
						<slot name="description" />
					</p>
				</template>

				<template v-if="props.closable">
					<div :class="[ns.e('close-btn'), { [ns.is('customed')]: props.closeText }]" @click="doClose">
						<template v-if="props.closeText">
							{{ props.closeText }}
						</template>

						<fb-icon v-else>
							<close />
						</fb-icon>
					</div>
				</template>
			</div>
		</div>
	</transition>
</template>

<script setup lang="ts">
import { computed, ref, useSlots } from 'vue';

import { VariantTypes, EffectTypes } from '@fastybird/web-ui-constants';
import { useNamespace } from '@fastybird/web-ui-hooks';
import { CloseComponentsMap, TypeComponentsMap } from '@fastybird/web-ui-utils';

import { FbIcon } from '../../icon';
import { alertEmits, alertProps } from './alert';

import type { Component } from 'vue';

defineOptions({
	name: 'FbAlert',
});

const props = defineProps(alertProps);
const emit = defineEmits(alertEmits);

const slots = useSlots();
const ns = useNamespace('alert');
const { close } = CloseComponentsMap;

const visible = ref<boolean>(true);

const icon = computed<Component | undefined>((): Component | undefined => {
	switch (props.variant) {
		case VariantTypes.INFO:
			return TypeComponentsMap.info;
		case VariantTypes.SUCCESS:
			return TypeComponentsMap.success;
		case VariantTypes.WARNING:
			return TypeComponentsMap.warning;
		case VariantTypes.ERROR:
			return TypeComponentsMap.error;
		default:
			return undefined;
	}
});

const iconClass = computed<(string | Record<string, boolean>)[]>((): (string | Record<string, boolean>)[] => [
	ns.e('icon'),
	{ [ns.is('big')]: 'title' in slots },
]);

const titleClass = computed<(string | Record<string, boolean>)[]>((): (string | Record<string, boolean>)[] => [
	ns.e('title'),
	{ [ns.em('title', 'with-description')]: 'description' in slots },
]);

const doClose = (evt: MouseEvent): void => {
	visible.value = false;

	emit('close', evt);
};
</script>
