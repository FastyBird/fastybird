<template>
	<div :class="ns.b()">
		<div :class="[ns.e('icon'), ns.is('animation', props.animation), ns.is('status', ['403', '404', '500'].includes(props.status))]">
			<slot name="icon">
				<component
					:is="icon"
					v-if="icon"
					:class="iconClass"
				/>
			</slot>
		</div>

		<div
			v-if="'title' in $slots || props.title"
			:class="ns.e('title')"
		>
			<slot name="title">
				<p>{{ props.title }}</p>
			</slot>
		</div>

		<div
			v-if="'subtitle' in $slots || props.subtitle"
			:class="ns.e('subtitle')"
		>
			<slot name="subtitle">
				<p>{{ props.subtitle }}</p>
			</slot>
		</div>

		<div
			v-if="'extra' in $slots"
			:class="ns.e('extra')"
		>
			<slot name="extra" />
		</div>

		<slot name="default" />
	</div>
</template>

<script lang="ts" setup>
import { computed } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';

import { IconComponentMap, IconMap, resultProps } from './result';

import type { Component } from 'vue';

defineOptions({
	name: 'FbResult',
});

const props = defineProps(resultProps);

const ns = useNamespace('result');

const iconClass = computed<string>((): string => {
	return props.status && IconMap[props.status] ? IconMap[props.status] : 'icon-info';
});

const icon = computed<Component>((): Component => {
	return iconClass.value in IconComponentMap
		? IconComponentMap[iconClass.value as (typeof IconMap)[keyof typeof IconMap]]
		: IconComponentMap['icon-info'];
});
</script>
