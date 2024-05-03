<template>
	<label :class="labelClass">
		<input
			v-if="props.trueValue || props.falseValue"
			v-model="model"
			:class="ns.be('button', 'original')"
			type="checkbox"
			:name="props.name"
			:tabindex="props.tabindex"
			:disabled="isDisabled"
			:true-value="props.trueValue"
			:false-value="props.falseValue"
			@change="handleChange"
			@focus="isFocused = true"
			@blur="isFocused = false"
			@click.stop
		/>
		<input
			v-else
			v-model="model"
			:class="ns.be('button', 'original')"
			type="checkbox"
			:name="props.name"
			:tabindex="props.tabindex"
			:disabled="isDisabled"
			:value="actualValue"
			@change="handleChange"
			@focus="isFocused = true"
			@blur="isFocused = false"
			@click.stop
		/>

		<span v-if="'default' in $slots || props.label" :class="ns.be('button', 'inner')" :style="isChecked ? activeStyle : undefined">
			<slot>{{ props.label }}</slot>
		</span>
	</label>
</template>

<script lang="ts" setup>
import { computed, inject, useSlots } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';

import { checkboxGroupContextKey } from './constants';
import { useCheckbox } from './hooks';
import { checkboxEmits, checkboxProps } from './checkbox';

import type { CSSProperties } from 'vue';

defineOptions({
	name: 'FbCheckboxButton',
});

const props = defineProps(checkboxProps);
defineEmits(checkboxEmits);
const ns = useNamespace('checkbox');

const slots = useSlots();

const { isFocused, isChecked, isDisabled, checkboxButtonSize, model, actualValue, handleChange } = useCheckbox(props, slots);
const checkboxGroup = inject(checkboxGroupContextKey, undefined);

const activeStyle = computed<CSSProperties>((): CSSProperties => {
	const fillValue = checkboxGroup?.fill?.value ?? '';

	return {
		backgroundColor: fillValue,
		borderColor: fillValue,
		color: checkboxGroup?.textColor?.value ?? '',
		boxShadow: fillValue ? `-1px 0 0 0 ${fillValue}` : undefined,
	};
});

const labelClass = computed<string[]>((): string[] => {
	return [
		ns.b('button'),
		ns.bm('button', checkboxButtonSize.value),
		ns.is('disabled', isDisabled.value),
		ns.is('checked', isChecked.value),
		ns.is('focus', isFocused.value),
	];
});
</script>
