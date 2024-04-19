<template>
	<component
		:is="!hasOwnLabel && isLabeledByFormItem ? 'span' : 'label'"
		:class="componentClass"
		:aria-controls="props.indeterminate ? props.controls : null"
		@click="onClickRoot"
	>
		<span :class="spanClass">
			<input
				v-if="props.trueValue || props.falseValue"
				:id="inputId"
				v-model="model"
				:class="ns.e('original')"
				type="checkbox"
				:indeterminate="props.indeterminate"
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
				:id="inputId"
				v-model="model"
				:class="ns.e('original')"
				type="checkbox"
				:indeterminate="props.indeterminate"
				:disabled="isDisabled"
				:value="actualValue"
				:name="props.name"
				:tabindex="props.tabindex"
				@change="handleChange"
				@focus="isFocused = true"
				@blur="isFocused = false"
				@click.stop
			/>
			<span :class="ns.e('inner')" />
		</span>
		<span
			v-if="hasOwnLabel"
			:class="ns.e('label')"
		>
			<slot />
			<template v-if="!('default' in $slots)">{{ props.label }}</template>
		</span>
	</component>
</template>

<script lang="ts" setup>
import { computed, useSlots } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';

import { checkboxEmits, checkboxProps } from './checkbox';
import { useCheckbox } from './hooks';

defineOptions({
	name: 'FbCheckbox',
});

const props = defineProps(checkboxProps);
defineEmits(checkboxEmits);
const ns = useNamespace('checkbox');

const slots = useSlots();

const { inputId, isLabeledByFormItem, isChecked, isDisabled, isFocused, checkboxSize, hasOwnLabel, model, actualValue, handleChange, onClickRoot } =
	useCheckbox(props, slots);

const componentClass = computed<string[]>((): string[] => {
	return [ns.b(), ns.m(checkboxSize.value), ns.is('disabled', isDisabled.value), ns.is('bordered', props.border), ns.is('checked', isChecked.value)];
});

const spanClass = computed<string[]>((): string[] => {
	return [
		ns.e('input'),
		ns.is('disabled', isDisabled.value),
		ns.is('checked', isChecked.value),
		ns.is('indeterminate', props.indeterminate),
		ns.is('focus', isFocused.value),
	];
});
</script>
