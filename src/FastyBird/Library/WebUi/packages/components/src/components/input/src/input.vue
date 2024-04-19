<template>
	<div
		v-bind="containerAttrs"
		:class="containerClass"
		:style="containerStyle"
		:role="props.containerRole"
		@mouseenter="handleMouseEnter"
		@mouseleave="handleMouseLeave"
	>
		<!-- input -->
		<template v-if="props.type !== 'textarea'">
			<!-- prepend slot -->
			<div
				v-if="'prepend' in $slots"
				:class="nsInput.be('group', 'prepend')"
			>
				<slot name="prepend" />
			</div>

			<div
				ref="wrapperRef"
				:class="wrapperClass"
			>
				<!-- prefix slot -->
				<span
					v-if="'prefix' in $slots || props.prefixIcon"
					:class="nsInput.e('prefix')"
				>
					<span :class="nsInput.e('prefix-inner')">
						<slot name="prefix" />
						<fb-icon
							v-if="props.prefixIcon"
							:class="nsInput.e('icon')"
						>
							<component :is="props.prefixIcon" />
						</fb-icon>
					</span>
				</span>

				<input
					:id="inputId"
					ref="input"
					:class="nsInput.e('inner')"
					v-bind="attrs"
					:minlength="props.minlength"
					:maxlength="props.maxlength"
					:type="props.showPassword ? (passwordVisible ? 'text' : 'password') : props.type"
					:disabled="inputDisabled"
					:readonly="props.readonly"
					:autocomplete="props.autocomplete"
					:tabindex="props.tabindex"
					:aria-label="props.label"
					:placeholder="props.placeholder"
					:style="props.inputStyle"
					:form="props.form"
					:autofocus="props.autofocus"
					@compositionstart="handleCompositionStart"
					@compositionupdate="handleCompositionUpdate"
					@compositionend="handleCompositionEnd"
					@input="handleInput"
					@focus="handleFocus"
					@blur="handleBlur"
					@change="handleChange"
					@keydown="handleKeydown"
				/>

				<!-- suffix slot -->
				<span
					v-if="suffixVisible"
					:class="nsInput.e('suffix')"
				>
					<span :class="nsInput.e('suffix-inner')">
						<template v-if="!showClear || !showPwdVisible || !isWordLimitVisible">
							<slot name="suffix" />
							<fb-icon
								v-if="props.suffixIcon"
								:class="nsInput.e('icon')"
							>
								<component :is="props.suffixIcon" />
							</fb-icon>
						</template>
						<fb-icon
							v-if="showClear"
							:class="[nsInput.e('icon'), nsInput.e('clear')]"
							@mousedown.prevent="NOOP"
							@click="clear"
						>
							<icon-close />
						</fb-icon>
						<fb-icon
							v-if="showPwdVisible"
							:class="[nsInput.e('icon'), nsInput.e('password')]"
							@click="handlePasswordVisible"
						>
							<component :is="passwordIcon" />
						</fb-icon>
						<span
							v-if="isWordLimitVisible"
							:class="nsInput.e('count')"
						>
							<span :class="nsInput.e('count-inner')"> {{ textLength }} / {{ props.maxlength }} </span>
						</span>
						<fb-icon
							v-if="validateState && validateIcon && needStatusIcon"
							:class="[nsInput.e('icon'), nsInput.e('validateIcon'), nsInput.is('loading', validateState === 'validating')]"
						>
							<component :is="validateIcon" />
						</fb-icon>
					</span>
				</span>
			</div>

			<!-- append slot -->
			<div
				v-if="'append' in $slots"
				:class="nsInput.be('group', 'append')"
			>
				<slot name="append" />
			</div>
		</template>

		<!-- textarea -->
		<template v-else>
			<textarea
				:id="inputId"
				ref="textarea"
				:class="nsTextarea.e('inner')"
				v-bind="attrs"
				:minlength="props.minlength"
				:maxlength="props.maxlength"
				:tabindex="props.tabindex"
				:disabled="inputDisabled"
				:readonly="props.readonly"
				:autocomplete="props.autocomplete"
				:style="textareaStyle"
				:aria-label="props.label"
				:placeholder="props.placeholder"
				:form="props.form"
				:autofocus="props.autofocus"
				@compositionstart="handleCompositionStart"
				@compositionupdate="handleCompositionUpdate"
				@compositionend="handleCompositionEnd"
				@input="handleInput"
				@focus="handleFocus"
				@blur="handleBlur"
				@change="handleChange"
				@keydown="handleKeydown"
			/>
			<span
				v-if="isWordLimitVisible"
				:style="countStyle"
				:class="nsInput.e('count')"
			>
				{{ textLength }} / {{ props.maxlength }}
			</span>
		</template>
	</div>
</template>

<script lang="ts" setup>
import { computed, nextTick, onMounted, ref, shallowRef, toRef, useAttrs as useRawAttrs, useSlots, watch } from 'vue';
import { isNil } from 'lodash-unified';

import { useResizeObserver } from '@vueuse/core';
import { FasCircleXmark as IconClose, FasEyeSlash as IconHide, FasEye as IconView } from '@fastybird/web-ui-icons';
import { NOOP, ValidateComponentsMap, debugWarn, isClient, isObject } from '@fastybird/web-ui-utils';
import { useAttrs, useCursor, useFocusController, useNamespace } from '@fastybird/web-ui-hooks';
import { UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';

import { useFormDisabled, useFormSize } from '../../form';
import { useFormItem, useFormItemInputId } from '../../form-item';
import { FbIcon } from '../../icon';
import { calcTextareaHeight } from './utils';
import { inputEmits, inputProps } from './input';

import type { Component, StyleValue } from 'vue';

type TargetElement = HTMLInputElement | HTMLTextAreaElement;

defineOptions({
	name: 'FbInput',
	inheritAttrs: false,
});

const props = defineProps(inputProps);
const emit = defineEmits(inputEmits);

const rawAttrs = useRawAttrs();
const slots = useSlots();

const containerAttrs = computed(() => {
	const comboBoxAttrs: Record<string, unknown> = {};

	if (props.containerRole === 'combobox') {
		comboBoxAttrs['aria-haspopup'] = rawAttrs['aria-haspopup'];
		comboBoxAttrs['aria-owns'] = rawAttrs['aria-owns'];
		comboBoxAttrs['aria-expanded'] = rawAttrs['aria-expanded'];
	}

	return comboBoxAttrs;
});

const containerClass = computed<(string | { [x: string]: boolean })[] | any>((): (string | { [x: string]: boolean })[] | any => [
	props.type === 'textarea' ? nsTextarea.b() : nsInput.b(),
	nsInput.m(inputSize.value),
	nsInput.is('disabled', inputDisabled.value),
	nsInput.is('exceed', inputExceed.value),
	{
		[nsInput.b('group')]: 'prepend' in slots || 'append' in slots,
		[nsInput.bm('group', 'append')]: 'append' in slots,
		[nsInput.bm('group', 'prepend')]: 'prepend' in slots,
		[nsInput.m('prefix')]: 'prefix' in slots || props.prefixIcon !== undefined,
		[nsInput.m('suffix')]: 'suffix' in slots || props.suffixIcon !== undefined || props.clearable || props.showPassword,
		[nsInput.bm('suffix', 'password-clear')]: showClear.value && showPwdVisible.value,
		[nsInput.b('hidden')]: props.type === 'hidden',
	},
	rawAttrs.class,
]);

const wrapperClass = computed<string[]>((): string[] => [nsInput.e('wrapper'), nsInput.is('focus', isFocused.value)]);

const attrs = useAttrs({
	excludeKeys: computed<string[]>(() => {
		return Object.keys(containerAttrs.value);
	}),
});
const { form: elForm, formItem: elFormItem } = useFormItem();
const { inputId } = useFormItemInputId(props, {
	formItemContext: elFormItem,
});
const inputSize = useFormSize();
const inputDisabled = useFormDisabled();
const nsInput = useNamespace('input');
const nsTextarea = useNamespace('textarea');

const input = shallowRef<HTMLInputElement>();
const textarea = shallowRef<HTMLTextAreaElement>();

const hovering = ref<boolean>(false);
const isComposing = ref<boolean>(false);
const passwordVisible = ref<boolean>(false);
const countStyle = ref<StyleValue>();
const textareaCalcStyle = shallowRef(props.inputStyle);

const _ref = computed(() => input.value || textarea.value);

const { wrapperRef, isFocused, handleFocus, handleBlur } = useFocusController(_ref, {
	afterBlur(): void {
		if (props.validateEvent) {
			elFormItem?.validate?.('blur').catch((err) => debugWarn(err));
		}
	},
});

const needStatusIcon = computed(() => elForm?.statusIcon ?? false);
const validateState = computed(() => elFormItem?.validateState || '');
const validateIcon = computed(() => validateState.value && ValidateComponentsMap[validateState.value]);
const passwordIcon = computed<Component>((): Component => (passwordVisible.value ? IconView : IconHide));
const containerStyle = computed<StyleValue>(() => [rawAttrs.style as StyleValue]);
const textareaStyle = computed<StyleValue>(() => [props.inputStyle, textareaCalcStyle.value, { resize: props.resize }]);
const nativeInputValue = computed<string>((): string => (isNil(props.modelValue) ? '' : String(props.modelValue)));
const showClear = computed<boolean>(
	(): boolean => props.clearable && !inputDisabled.value && !props.readonly && !!nativeInputValue.value && (isFocused.value || hovering.value)
);
const showPwdVisible = computed<boolean>(
	(): boolean =>
		props.showPassword && !inputDisabled.value && !props.readonly && !!nativeInputValue.value && (!!nativeInputValue.value || isFocused.value)
);
const isWordLimitVisible = computed<boolean>(
	(): boolean =>
		props.showWordLimit &&
		!!props.maxlength &&
		(props.type === 'text' || props.type === 'textarea') &&
		!inputDisabled.value &&
		!props.readonly &&
		!props.showPassword
);
const textLength = computed<number>((): number => nativeInputValue.value.length);
const inputExceed = computed<boolean>(
	(): boolean =>
		// show exceed style if length of initial value greater then maxlength
		!!isWordLimitVisible.value && textLength.value > Number(props.maxlength)
);
const suffixVisible = computed<boolean>(
	(): boolean =>
		!!slots.suffix ||
		!!props.suffixIcon ||
		showClear.value ||
		props.showPassword ||
		isWordLimitVisible.value ||
		(!!validateState.value && needStatusIcon.value)
);

const [recordCursor, setCursor] = useCursor(input);

useResizeObserver(textarea, (entries): void => {
	onceInitSizeTextarea();

	if (!isWordLimitVisible.value || props.resize !== 'both') {
		return;
	}

	const entry = entries[0];
	const { width } = entry.contentRect;

	countStyle.value = {
		/** right: 100% - width + padding(15) + right(6) */
		right: `calc(100% - ${width + 15 + 6}px)`,
	};
});

const resizeTextarea = (): void => {
	const { type, autosize } = props;

	if (!isClient || type !== 'textarea' || !textarea.value) {
		return;
	}

	if (autosize) {
		const minRows = isObject(autosize) ? autosize.minRows : undefined;
		const maxRows = isObject(autosize) ? autosize.maxRows : undefined;
		const textareaStyle = calcTextareaHeight(textarea.value, minRows, maxRows);

		// If the scrollbar is displayed, the height of the textarea needs more space than the calculated height.
		// If set textarea height in this case, the scrollbar will not hide.
		// So we need to hide scrollbar first, and reset it in next tick.
		textareaCalcStyle.value = {
			overflowY: 'hidden',
			...textareaStyle,
		};

		nextTick((): void => {
			// NOTE: Force repaint to make sure the style set above is applied.
			textarea.value!.offsetHeight;
			textareaCalcStyle.value = textareaStyle;
		});
	} else {
		textareaCalcStyle.value = {
			minHeight: calcTextareaHeight(textarea.value).minHeight,
		};
	}
};

const createOnceInitResize = (resizeTextarea: () => void): (() => void) => {
	let isInit = false;

	return (): void => {
		if (isInit || !props.autosize) {
			return;
		}

		const isElHidden = textarea.value?.offsetParent === null;

		if (!isElHidden) {
			resizeTextarea();
			isInit = true;
		}
	};
};

const onceInitSizeTextarea = createOnceInitResize(resizeTextarea);

const setNativeInputValue = (): void => {
	const input = _ref.value;
	const formatterValue = props.formatter ? props.formatter(nativeInputValue.value) : nativeInputValue.value;

	if (!input || input.value === formatterValue) {
		return;
	}

	input.value = formatterValue;
};

const handleInput = async (event: Event): Promise<void> => {
	recordCursor();

	let { value } = event.target as TargetElement;

	if (props.formatter) {
		value = props.parser ? props.parser(value) : value;
	}

	// should not emit input during composition
	if (isComposing.value) {
		return;
	}

	// should remove the following line when we don't support IE
	if (value === nativeInputValue.value) {
		setNativeInputValue();

		return;
	}

	emit(UPDATE_MODEL_EVENT, value);
	emit('input', value);

	// ensure native input value is controlled
	await nextTick();

	setNativeInputValue();
	setCursor();
};

const handleChange = (event: Event): void => {
	emit('change', (event.target as TargetElement).value);
};

const handleCompositionStart = (event: CompositionEvent): void => {
	emit('compositionstart', event);
	isComposing.value = true;
};

const handleCompositionUpdate = (event: CompositionEvent): void => {
	emit('compositionupdate', event);
	isComposing.value = false;
};

const handleCompositionEnd = (event: CompositionEvent): void => {
	emit('compositionend', event);

	if (isComposing.value) {
		isComposing.value = false;
		handleInput(event);
	}
};

const handlePasswordVisible = (): void => {
	passwordVisible.value = !passwordVisible.value;
	focus();
};

const focus = async (): Promise<void> => {
	await nextTick();
	_ref.value?.focus();
};

const blur = (): void => {
	_ref.value?.blur();
};

const handleMouseLeave = (evt: MouseEvent): void => {
	hovering.value = false;
	emit('mouseleave', evt);
};

const handleMouseEnter = (evt: MouseEvent): void => {
	hovering.value = true;
	emit('mouseenter', evt);
};

const handleKeydown = (evt: KeyboardEvent): void => {
	emit('keydown', evt);
};

const select = (): void => {
	_ref.value?.select();
};

const clear = (): void => {
	emit(UPDATE_MODEL_EVENT, '');
	emit('change', '');
	emit('clear');
	emit('input', '');
};

watch(
	(): string | number | null | undefined => props.modelValue,
	(): void => {
		nextTick(() => resizeTextarea());

		if (props.validateEvent) {
			elFormItem?.validate?.('change').catch((err) => debugWarn(err));
		}
	}
);

// native input value is set explicitly
// do not use v-model / :value in template
watch(nativeInputValue, () => setNativeInputValue());

// when change between <input> and <textarea>,
// update DOM dependent value and styles
watch(
	(): string => props.type,
	async (): Promise<void> => {
		await nextTick();
		setNativeInputValue();
		resizeTextarea();
	}
);

onMounted((): void => {
	if (!props.formatter && props.parser) {
		debugWarn('FbInput', 'If you set the parser, you also need to set the formatter.');
	}
	setNativeInputValue();
	nextTick(resizeTextarea);
});

defineExpose({
	/** @description HTML input element */
	input,
	/** @description HTML textarea element */
	textarea,
	/** @description HTML element, input or textarea */
	ref: _ref,
	/** @description style of textarea. */
	textareaStyle,

	/** @description from props (used on unit test) */
	autosize: toRef(props, 'autosize'),

	/** @description HTML input element native method */
	focus,
	/** @description HTML input element native method */
	blur,
	/** @description HTML input element native method */
	select,
	/** @description clear input value */
	clear,
	/** @description resize textarea. */
	resizeTextarea,
});
</script>
