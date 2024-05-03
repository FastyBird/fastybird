<template>
	<div :class="switchClass" @click.prevent="switchValue">
		<input
			:id="inputId"
			ref="input"
			:class="ns.e('input')"
			type="checkbox"
			role="switch"
			:aria-checked="checked"
			:aria-disabled="switchDisabled"
			:aria-label="props.label"
			:name="props.name"
			:true-value="props.activeValue"
			:false-value="props.inactiveValue"
			:disabled="switchDisabled"
			:tabindex="props.tabindex"
			@change="handleChange"
			@keydown.enter="switchValue"
		/>
		<span v-if="!props.inlinePrompt && (props.inactiveIcon || props.inactiveText)" :class="labelLeftClass">
			<fb-icon v-if="props.inactiveIcon">
				<component :is="props.inactiveIcon" />
			</fb-icon>
			<span v-if="!props.inactiveIcon && props.inactiveText" :aria-hidden="checked">
				{{ props.inactiveText }}
			</span>
		</span>
		<span ref="core" :class="ns.e('core')" :style="coreStyle">
			<div v-if="props.inlinePrompt" :class="ns.e('inner')">
				<template v-if="props.activeIcon || props.inactiveIcon">
					<fb-icon :class="ns.is('icon')">
						<component :is="checked ? props.activeIcon : props.inactiveIcon" />
					</fb-icon>
				</template>
				<template v-else-if="props.activeText || props.inactiveText">
					<span :class="ns.is('text')" :aria-hidden="!checked">
						{{ checked ? props.activeText : props.inactiveText }}
					</span>
				</template>
			</div>
			<div :class="ns.e('action')">
				<fb-spinner v-if="props.loading" :class="ns.is('loading')" />
				<slot v-else-if="checked" name="active-action">
					<fb-icon v-if="props.activeActionIcon">
						<component :is="props.activeActionIcon" />
					</fb-icon>
				</slot>
				<slot v-else-if="!checked" name="inactive-action">
					<fb-icon v-if="props.inactiveActionIcon">
						<component :is="props.inactiveActionIcon" />
					</fb-icon>
				</slot>
			</div>
		</span>
		<span v-if="!props.inlinePrompt && (props.activeIcon || props.activeText)" :class="labelRightClass">
			<fb-icon v-if="props.activeIcon">
				<component :is="props.activeIcon" />
			</fb-icon>
			<span v-if="!props.activeIcon && props.activeText" :aria-hidden="!checked">{{ props.activeText }}</span>
		</span>
	</div>
</template>

<script lang="ts" setup>
import { computed, getCurrentInstance, nextTick, onMounted, ref, watch } from 'vue';

import { isPromise } from '@vue/shared';
import { addUnit, debugWarn, isBoolean, throwError } from '@fastybird/web-ui-utils';
import { CHANGE_EVENT, INPUT_EVENT, UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';
import { useNamespace } from '@fastybird/web-ui-hooks';

import { useFormDisabled, useFormSize } from '../../form';
import { useFormItem, useFormItemInputId } from '../../form-item';
import { FbIcon } from '../../icon';
import { FbSpinner } from '../../spinner';
import { switchEmits, switchProps } from './switch';

import type { CSSProperties } from 'vue';

defineOptions({
	name: 'FbSwitch',
});

const props = defineProps(switchProps);
const emit = defineEmits(switchEmits);
const ns = useNamespace('switch');

const instance = getCurrentInstance();

const { formItem } = useFormItem();
const switchSize = useFormSize();

const { inputId } = useFormItemInputId(props, {
	formItemContext: formItem,
});

const switchDisabled = useFormDisabled(computed(() => props.loading));
const isControlled = ref<boolean>(props.modelValue !== false);
const input = ref<HTMLInputElement | undefined>();
const core = ref<HTMLSpanElement | undefined>();

const switchClass = computed<string[]>((): string[] => [
	ns.b(),
	ns.m('size-' + switchSize.value),
	ns.m('variant-' + props.variant),
	ns.is('disabled', switchDisabled.value),
	ns.is('checked', checked.value),
]);

const labelLeftClass = computed<string[]>((): string[] => [ns.e('label'), ns.em('label', 'left'), ns.is('active', !checked.value)]);

const labelRightClass = computed<string[]>((): string[] => [ns.e('label'), ns.em('label', 'right'), ns.is('active', checked.value)]);

const coreStyle = computed<CSSProperties>(
	(): CSSProperties => ({
		width: addUnit(props.width),
	})
);

const actualValue = computed<boolean | number | string>((): boolean | number | string => {
	return isControlled.value ? props.modelValue : false;
});

const checked = computed<boolean>((): boolean => actualValue.value === props.activeValue);

if (![props.activeValue, props.inactiveValue].includes(actualValue.value)) {
	emit(UPDATE_MODEL_EVENT, props.inactiveValue);
	emit(CHANGE_EVENT, props.inactiveValue);
	emit(INPUT_EVENT, props.inactiveValue);
}

const handleChange = async (): Promise<void> => {
	const val = checked.value ? props.inactiveValue : props.activeValue;

	emit(UPDATE_MODEL_EVENT, val);
	emit(CHANGE_EVENT, val);
	emit(INPUT_EVENT, val);

	await nextTick();

	input.value!.checked = checked.value;
};

const switchValue = (): void => {
	if (switchDisabled.value) {
		return;
	}

	const { beforeChange } = props;

	if (!beforeChange) {
		handleChange();

		return;
	}

	const shouldChange = beforeChange();

	const isPromiseOrBool = [isPromise(shouldChange), isBoolean(shouldChange)].includes(true);

	if (!isPromiseOrBool) {
		throwError(instance!.type.name!, 'beforeChange must return type `Promise<boolean>` or `boolean`');
	}

	if (isPromise(shouldChange)) {
		shouldChange
			.then((result) => {
				if (result) {
					handleChange();
				}
			})
			.catch((e) => {
				debugWarn('FbSwitch', `some error occurred: ${e}`);
			});
	} else if (shouldChange) {
		handleChange();
	}
};

const focus = (): void => {
	input.value?.focus?.();
};

onMounted((): void => {
	input.value!.checked = checked.value;
});

watch(
	(): boolean => checked.value,
	(val: boolean) => {
		input.value!.checked = val;

		if (props.validateEvent) {
			formItem?.validate?.('change').catch((err) => debugWarn(err));
		}
	}
);

watch(
	(): number | string | boolean => props.modelValue,
	(): void => {
		isControlled.value = true;
	}
);

defineExpose({
	/** @description manual focus to the switch component */
	focus,
	/** @description whether Switch is checked */
	checked,
});
</script>
