<template>
	<div
		ref="selectRef"
		v-click-outside:[popperRef]="handleClickOutside"
		:class="[nsSelect.b(), nsSelect.m(selectSize)]"
		@mouseenter="state.inputHovering = true"
		@mouseleave="state.inputHovering = false"
		@click.stop="handleToggleMenu"
	>
		<fb-tooltip
			ref="tooltipRef"
			:visible="dropdownMenuVisible"
			:placement="props.placement"
			:teleported="props.teleported"
			:popper-class="popperClass"
			:popper-options="props.popperOptions"
			:fallback-placements="props.fallbackPlacements"
			:effect="props.effect"
			pure
			trigger="click"
			:transition="`${nsSelect.namespace.value}-zoom-in-top`"
			:stop-popper-mouse-event="false"
			:gpu-acceleration="false"
			:persistent="props.persistent"
			@before-show="handleMenuEnter"
			@hide="state.isBeforeHide = false"
		>
			<template #default>
				<div
					ref="wrapperRef"
					:class="[
						nsSelect.e('wrapper'),
						nsSelect.is('focused', isFocused),
						nsSelect.is('hovering', state.inputHovering),
						nsSelect.is('filterable', props.filterable),
						nsSelect.is('disabled', selectDisabled),
					]"
				>
					<div v-if="'prefix' in $slots" ref="prefixRef" :class="nsSelect.e('prefix')">
						<slot name="prefix" />
					</div>

					<div ref="selectionRef" :class="[nsSelect.e('selection'), nsSelect.is('near', props.multiple && !('prefix' in $slots) && !!selectedCnt)]">
						<slot v-if="props.multiple" name="tag">
							<div v-for="item in showTagList || []" :key="getValueKey(item)" :class="nsSelect.e('selected-item')">
								<fb-tag
									:closable="!selectDisabled && !item.isDisabled"
									:size="collapseTagSize"
									:variant="props.tagVariant"
									disable-transitions
									:style="tagStyle"
									@close="handleDeleteTag($event, item)"
								>
									<span :class="nsSelect.e('tags-text')">
										{{ item.currentLabel }}
									</span>
								</fb-tag>
							</div>

							<fb-tooltip
								v-if="props.collapseTags && selectedCnt > props.maxCollapseTags"
								ref="tagTooltipRef"
								:disabled="dropdownMenuVisible || !props.collapseTagsTooltip"
								:fallback-placements="['bottom', 'top', 'right', 'left']"
								:effect="props.effect"
								placement="bottom"
								:teleported="props.teleported"
							>
								<template #default>
									<div ref="collapseItemRef" :class="nsSelect.e('selected-item')">
										<fb-tag :closable="false" :size="collapseTagSize" :variant="props.tagVariant" disable-transitions :style="collapseTagStyle">
											<span :class="nsSelect.e('tags-text')"> + {{ selectedCnt - props.maxCollapseTags }} </span>
										</fb-tag>
									</div>
								</template>

								<template #content>
									<div ref="tagMenuRef" :class="nsSelect.e('selection')">
										<div v-for="item in collapseTagList" :key="getValueKey(item)" :class="nsSelect.e('selected-item')">
											<fb-tag
												class="in-tooltip"
												:closable="!selectDisabled && !item.isDisabled"
												:size="collapseTagSize"
												:variant="props.tagVariant"
												disable-transitions
												@close="handleDeleteTag($event, item)"
											>
												<span :class="nsSelect.e('tags-text')">
													{{ item.currentLabel }}
												</span>
											</fb-tag>
										</div>
									</div>
								</template>
							</fb-tooltip>
						</slot>

						<div v-if="!selectDisabled" :class="[nsSelect.e('selected-item'), nsSelect.e('input-wrapper'), nsSelect.is('hidden', !props.filterable)]">
							<input
								:id="inputId"
								ref="inputRef"
								v-model="state.inputValue"
								type="text"
								:class="[nsSelect.e('input'), nsSelect.is(selectSize)]"
								:disabled="selectDisabled"
								:autocomplete="props.autocomplete"
								:style="inputStyle"
								:readonly="!props.filterable"
								:aria-activedescendant="hoverOption?.id || ''"
								:aria-controls="contentId"
								:aria-expanded="dropdownMenuVisible"
								:aria-label="props.ariaLabel"
								aria-autocomplete="none"
								aria-haspopup="listbox"
								spellcheck="false"
								role="combobox"
								@focus="handleFocus"
								@blur="handleBlur"
								@keydown.down.stop.prevent="navigateOptions('next')"
								@keydown.up.stop.prevent="navigateOptions('prev')"
								@keydown.esc.stop.prevent="handleEsc"
								@keydown.enter.stop.prevent="handleSelectOption"
								@keydown.delete.stop="handleDeletePrevTag"
								@compositionstart="handleCompositionStart"
								@compositionupdate="handleCompositionUpdate"
								@compositionend="handleCompositionEnd"
								@input="onInput"
								@click.stop="handleToggleMenu"
							/>
							<span
								v-if="props.filterable"
								ref="calculatorRef"
								aria-hidden="true"
								:class="nsSelect.e('input-calculator')"
								v-text="state.inputValue"
							/>
						</div>

						<div
							v-if="shouldShowPlaceholder"
							:class="[
								nsSelect.e('selected-item'),
								nsSelect.e('placeholder'),
								nsSelect.is('transparent', !hasModelValue || (expanded && !state.inputValue)),
							]"
						>
							<span>{{ currentPlaceholder }}</span>
						</div>
					</div>

					<div ref="suffixRef" :class="nsSelect.e('suffix')">
						<fb-icon v-if="iconComponent && !showClose" :class="[nsSelect.e('caret'), nsSelect.e('icon'), iconReverse]">
							<component :is="iconComponent" />
						</fb-icon>

						<fb-icon v-if="showClose" :class="[nsSelect.e('caret'), nsSelect.e('icon')]" @click="handleClearClick">
							<component :is="props.clearIcon || closeIcon" />
						</fb-icon>

						<fb-icon v-if="validateState && validateIcon" :class="[nsInput.e('icon'), nsInput.e('validateIcon')]">
							<component :is="validateIcon" />
						</fb-icon>
					</div>
				</div>
			</template>

			<template #content>
				<fb-select-dropdown ref="menuRef" :popper-class="props.popperClass" :multiple="props.multiple" :fit-input-width="props.fitInputWidth">
					<div v-if="'header' in $slots" :class="nsSelect.be('dropdown', 'header')">
						<slot name="header" />
					</div>

					<fb-scrollbar
						v-show="state.options.size > 0 && !props.loading"
						:id="contentId"
						ref="scrollbarRef"
						tag="ul"
						:wrap-class="nsSelect.be('dropdown', 'wrap')"
						:view-class="nsSelect.be('dropdown', 'list')"
						:class="[nsSelect.is('empty', filteredOptionsCount === 0)]"
						role="listbox"
						:aria-label="props.ariaLabel"
						aria-orientation="vertical"
					>
						<fb-option v-if="showNewOption" :value="state.inputValue" :created="true" />
						<fb-options>
							<slot />
						</fb-options>
					</fb-scrollbar>

					<div v-if="'loading' in $slots && props.loading" :class="nsSelect.be('dropdown', 'loading')">
						<slot name="loading" />
					</div>

					<div v-else-if="props.loading || filteredOptionsCount === 0" :class="nsSelect.be('dropdown', 'empty')">
						<slot name="empty">
							<span>{{ emptyText }}</span>
						</slot>
					</div>

					<div v-if="'footer' in $slots" :class="nsSelect.be('dropdown', 'footer')">
						<slot name="footer" />
					</div>
				</fb-select-dropdown>
			</template>
		</fb-tooltip>
	</div>
</template>

<script lang="ts" setup>
import { computed, nextTick, onMounted, provide, reactive, ref, toRaw, watch, watchEffect } from 'vue';
import { debounce as lodashDebounce, findLastIndex, get, isEqual, isNil } from 'lodash';

import { hasOwn, isArray, isObject, toRawType } from '@vue/shared';
import { useResizeObserver } from '@vueuse/core';
import { CHANGE_EVENT, ComponentSize, ComponentSizeTypes, EVENT_CODE, UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';
import { ClickOutside as vClickOutside } from '@fastybird/web-ui-directives';
import { useFocusController, useId, useLocale, useNamespace } from '@fastybird/web-ui-hooks';
import {
	debugWarn,
	isClient,
	isFunction,
	isNumber,
	isUndefined,
	scrollIntoView,
	ValidateComponentsMap,
	CloseComponentsMap,
	ArrowComponentsMap,
} from '@fastybird/web-ui-utils';

import { FbIcon } from '../../icon';
import { FbScrollbar } from '../../scrollbar';
import { FbTag } from '../../tag';
import { FbTooltip } from '../../tooltip';
import FbOption from './option.vue';
import FbSelectDropdown from './select-dropdown.vue';
import FbOptions from './options';
import { selectContextKey } from './constants';
import { selectEmits, SelectOption, selectProps } from './select';
import { useFormSize } from '../../form';
import { FormItemValidateState, useFormItem, useFormItemInputId } from '../../form-item';

import type { Component, StyleValue } from 'vue';
import type { SelectContext } from './constants';
import type { OptionProxy } from './option';

const COMPONENT_NAME = 'FbSelect';
const MINIMUM_INPUT_WIDTH = 11;

defineOptions({
	name: COMPONENT_NAME,
});

const props = defineProps(selectProps);
const emit = defineEmits(selectEmits);

const { t } = useLocale();
const contentId = useId();
const nsSelect = useNamespace('select');
const nsInput = useNamespace('input');

const { close: closeIcon } = CloseComponentsMap;
const { down: arrowDown } = ArrowComponentsMap;

const { form, formItem } = useFormItem();
const { inputId } = useFormItemInputId(props, { formItemContext: formItem });

const selectRef = ref<HTMLElement | undefined>();
const selectionRef = ref<HTMLElement | undefined>();
const tooltipRef = ref<InstanceType<typeof FbTooltip> | undefined>();
const tagTooltipRef = ref<InstanceType<typeof FbTooltip> | undefined>();
const inputRef = ref<HTMLInputElement | undefined>();
const calculatorRef = ref<HTMLElement | undefined>();
const prefixRef = ref<HTMLElement | undefined>();
const suffixRef = ref<HTMLElement | undefined>();
const menuRef = ref<HTMLElement | undefined>();
const tagMenuRef = ref<HTMLElement | undefined>();
const collapseItemRef = ref<HTMLElement | undefined>();
const scrollbarRef = ref<{ handleScroll: () => void } | undefined>();

const state = reactive<SelectContext['state']>({
	inputValue: '',
	options: new Map<string | number | boolean | object, OptionProxy>(),
	cachedOptions: new Map<string | number | boolean | object, OptionProxy>(),
	disabledOptions: new Map<string | number | boolean | object, OptionProxy>(),
	optionValues: [],
	selected: props.multiple ? [] : undefined,
	selectionWidth: 0,
	calculatorWidth: 0,
	collapseItemWidth: 0,
	selectedLabel: '',
	hoveringIndex: -1,
	previousQuery: null,
	inputHovering: false,
	menuVisibleOnFocus: false,
	isBeforeHide: false,
});

const { wrapperRef, isFocused, handleFocus, handleBlur } = useFocusController(inputRef, {
	afterFocus(): void {
		if (props.automaticDropdown && !expanded.value) {
			expanded.value = true;
			state.menuVisibleOnFocus = true;
		}
	},
	beforeBlur(event: FocusEvent): boolean | undefined {
		return tooltipRef.value?.isFocusInsideContent(event) || tagTooltipRef.value?.isFocusInsideContent(event);
	},
	afterBlur(): void {
		expanded.value = false;
		state.menuVisibleOnFocus = false;
	},
});

const expanded = ref<boolean>(false);
const hoverOption = ref<OptionProxy | undefined>();

const popperClass = computed<string | string[] | Record<string, boolean> | undefined>((): string | string[] | Record<string, boolean> | undefined => {
	if (isArray(props.popperClass)) {
		return [nsSelect.e('popper'), ...props.popperClass];
	}

	if (isObject(props.popperClass)) {
		return [nsSelect.e('popper')];
	}

	return props.popperClass ? [nsSelect.e('popper'), props.popperClass] : nsSelect.e('popper');
});

const selectDisabled = computed<boolean>((): boolean => props.disabled || form?.disabled || false);

const hasEmptyStringOption = computed<boolean>((): boolean => optionsArray.value.some((option) => option.value === ''));

const hasModelValue = computed<boolean>((): boolean => {
	return props.multiple
		? isArray(props.modelValue) && props.modelValue.length > 0
		: !isNil(props.modelValue) && (props.modelValue !== '' || hasEmptyStringOption.value);
});

const showClose = computed<boolean>((): boolean => {
	return props.clearable && !selectDisabled.value && state.inputHovering && hasModelValue.value;
});

const iconComponent = computed<string | Component | undefined>((): string | Component | undefined =>
	props.remote && props.filterable && !props.remoteShowSuffix ? undefined : props.suffixIcon || arrowDown
);
const iconReverse = computed<string>((): string => nsSelect.is('reverse', !!iconComponent.value && expanded.value));

const validateState = computed<FormItemValidateState | undefined>((): FormItemValidateState | undefined => formItem?.validateState || undefined);
const validateIcon = computed<Component | undefined>((): Component | undefined =>
	validateState.value ? ValidateComponentsMap[validateState.value] : undefined
);

const debounce = computed<number>((): number => (props.remote ? 300 : 0));

const selectedCnt = computed<number>((): number => {
	if (isArray(state.selected)) {
		return state.selected.length;
	}

	return state.selected ? 1 : 0;
});

const emptyText = computed<string | false | null>((): string | false | null => {
	if (props.loading) {
		return props.loadingText || t('component.select.loading');
	} else {
		if (props.remote && !state.inputValue && state.options.size === 0) {
			return false;
		}

		if (props.filterable && state.inputValue && state.options.size > 0 && filteredOptionsCount.value === 0) {
			return props.noMatchText || t('component.select.noMatch');
		}

		if (state.options.size === 0) {
			return props.noDataText || t('component.select.noData');
		}
	}

	return null;
});

const filteredOptionsCount = computed<number>((): number => optionsArray.value.filter((option: OptionProxy) => option.state.visible).length);

const optionsArray = computed<OptionProxy[]>((): OptionProxy[] => {
	const list = Array.from(state.options.values()) as unknown as OptionProxy[];

	const newList: OptionProxy[] = [];

	state.optionValues.forEach((item) => {
		if (state.options.has(item)) {
			newList.push(state.options.get(item) as unknown as OptionProxy);
		}
	});

	return newList.length >= list.length ? newList : list;
});

const cachedOptionsArray = computed<OptionProxy[]>((): OptionProxy[] => {
	const values: OptionProxy[] = [];

	state.cachedOptions.forEach((item) => {
		values.push(item as unknown as OptionProxy);
	});

	return values;
});

const showNewOption = computed<boolean>((): boolean => {
	const hasExistingOption = optionsArray.value
		.filter((option) => {
			return !option.created;
		})
		.some((option) => {
			return option.currentLabel === state.inputValue;
		});

	return props.filterable && props.allowCreate && state.inputValue !== '' && !hasExistingOption;
});

const updateOptions = (): void => {
	if (props.filterable && isFunction(props.filterMethod)) {
		return;
	}

	if (props.filterable && props.remote && isFunction(props.remoteMethod)) {
		return;
	}

	optionsArray.value.forEach((option) => {
		option.updateOption(state.inputValue);
	});
};

const selectSize = useFormSize();

const collapseTagSize = computed<ComponentSize>(
	(): ComponentSize => ([ComponentSizeTypes.SMALL].includes(selectSize.value) ? ComponentSizeTypes.SMALL : ComponentSizeTypes.DEFAULT)
);

const dropdownMenuVisible = computed<boolean>({
	get(): boolean {
		return expanded.value && emptyText.value !== false;
	},
	set(val: boolean): void {
		expanded.value = val;
	},
});

const shouldShowPlaceholder = computed<boolean>((): boolean => {
	if (isArray(props.modelValue)) {
		return props.modelValue.length === 0 && !state.inputValue;
	}

	return props.filterable ? !state.inputValue : true;
});

const currentPlaceholder = computed<string>((): string => {
	const _placeholder = props.placeholder ?? t('component.select.placeholder');

	return props.multiple || !hasModelValue.value ? _placeholder : state.selectedLabel;
});

watch(
	(): string | number | boolean | object | (string | number | boolean | object)[] | undefined => props.modelValue,
	(
		val: string | number | boolean | object | (string | number | boolean | object)[] | undefined,
		oldVal: string | number | boolean | object | (string | number | boolean | object)[] | undefined
	) => {
		if (props.multiple) {
			if (props.filterable && !props.reserveKeyword) {
				state.inputValue = '';
				handleQueryChange('');
			}
		}

		setSelected();

		if (!isEqual(val, oldVal) && props.validateEvent) {
			formItem?.validate('change').catch((err) => debugWarn(err));
		}
	},
	{
		flush: 'post',
		deep: true,
	}
);

watch(
	(): boolean => expanded.value,
	(val: boolean) => {
		if (val) {
			handleQueryChange(state.inputValue);
		} else {
			state.inputValue = '';
			state.previousQuery = null;
			state.isBeforeHide = true;
		}

		emit('visible-change', val);
	}
);

watch(
	// fix `Array.prototype.push/splice/..` cannot trigger non-deep watcher
	// https://github.com/vuejs/vue-next/issues/2116
	() => state.options.entries(),
	(): void => {
		if (!isClient) {
			return;
		}

		// tooltipRef.value?.updatePopper?.()
		const inputs = selectRef.value?.querySelectorAll('input') || [];

		if (
			(!props.filterable && !props.defaultFirstOption && !isUndefined(props.modelValue)) ||
			!Array.from(inputs).includes(document.activeElement as HTMLInputElement)
		) {
			setSelected();
		}

		if (props.defaultFirstOption && (props.filterable || props.remote) && filteredOptionsCount.value) {
			checkDefaultFirstOption();
		}
	},
	{
		flush: 'post',
	}
);

watch(
	(): number => state.hoveringIndex,
	(val: number): void => {
		if (isNumber(val) && val > -1) {
			hoverOption.value = optionsArray.value[val] || undefined;
		} else {
			hoverOption.value = undefined;
		}

		optionsArray.value.forEach((option) => {
			option.state.hover = hoverOption.value === option;
		});
	}
);

watchEffect((): void => {
	// Anything could cause options changed, then update options
	// If you want to control it by condition, write here
	if (state.isBeforeHide) {
		return;
	}

	updateOptions();
});

const handleQueryChange = (val: string): void => {
	if (state.previousQuery === val) {
		return;
	}

	state.previousQuery = val;

	if (props.filterable && isFunction(props.filterMethod)) {
		props.filterMethod(val);
	} else if (props.filterable && props.remote && isFunction(props.remoteMethod)) {
		props.remoteMethod(val);
	}

	if (props.defaultFirstOption && (props.filterable || props.remote) && filteredOptionsCount.value) {
		nextTick(checkDefaultFirstOption);
	} else {
		nextTick(updateHoveringIndex);
	}
};

/**
 * find and highlight first option as default selected
 * @remark
 * - if the first option in dropdown list is user-created,
 *   it would be at the end of the optionsArray
 *   so find it and set hover.
 *   (NOTE: there must be only one user-created option in dropdown list with query)
 * - if there's no user-created option in list, just find the first one as usual
 *   (NOTE: exclude options that are disabled or in disabled-group)
 */
const checkDefaultFirstOption = (): void => {
	const optionsInDropdown = optionsArray.value.filter((n) => n.state.visible && !n.disabled && !n.state.groupDisabled);
	const userCreatedOption = optionsInDropdown.find((n) => n.created);
	const firstOriginOption = optionsInDropdown[0];

	state.hoveringIndex = getValueIndex(optionsArray.value, userCreatedOption || firstOriginOption);
};

const setSelected = (): void => {
	if (!props.multiple) {
		const option = getOption(props.modelValue);

		state.selectedLabel = option.currentLabel;
		state.selected = option;

		return;
	} else {
		state.selectedLabel = '';
	}

	const result: SelectOption[] = [];

	if (isArray(props.modelValue)) {
		props.modelValue.forEach((value) => {
			result.push(getOption(value));
		});
	}

	state.selected = result;
};

const getOption = (value: string | number | boolean | object | (string | number | boolean | object)[] | undefined): SelectOption => {
	let option;

	const isObjectValue = toRawType(value).toLowerCase() === 'object';
	const isNull = toRawType(value).toLowerCase() === 'null';
	const isUndefined = toRawType(value).toLowerCase() === 'undefined';

	for (let i = state.cachedOptions.size - 1; i >= 0; i--) {
		const cachedOption = cachedOptionsArray.value[i];
		const isEqualValue = isObjectValue ? get(cachedOption.value, props.valueKey) === get(value, props.valueKey) : cachedOption.value === value;

		if (isEqualValue) {
			option = {
				value,
				currentLabel: cachedOption.currentLabel,
				isDisabled: cachedOption.isDisabled,
			};

			break;
		}
	}

	if (option) {
		return {
			value: option.value,
			currentLabel: option.currentLabel,
			isDisabled: option.isDisabled,
		};
	}

	const label = isObjectValue ? (hasOwn(value as object, 'label') ? (value as any).label : '') : !isNull && !isUndefined ? value : '';

	return {
		value,
		currentLabel: label,
	};
};

const updateHoveringIndex = (): void => {
	if (!props.multiple) {
		state.hoveringIndex = optionsArray.value.findIndex((item) => {
			return getValueKey(item) === getValueKey(state.selected as SelectOption);
		});
	} else {
		state.hoveringIndex = optionsArray.value.findIndex((item) =>
			(state.selected as SelectOption[]).some((selected) => getValueKey(selected) === getValueKey(item))
		);
	}
};

const resetSelectionWidth = (): void => {
	state.selectionWidth = selectionRef.value!.getBoundingClientRect().width;
};

const resetCalculatorWidth = (): void => {
	state.calculatorWidth = calculatorRef.value!.getBoundingClientRect().width;
};

const resetCollapseItemWidth = (): void => {
	state.collapseItemWidth = collapseItemRef.value!.getBoundingClientRect().width;
};

const updateTooltip = (): void => {
	tooltipRef.value?.updatePopper?.();
};

const updateTagTooltip = (): void => {
	tagTooltipRef.value?.updatePopper?.();
};

const onInputChange = (): void => {
	if (state.inputValue.length > 0 && !expanded.value) {
		expanded.value = true;
	}

	handleQueryChange(state.inputValue);
};

const onInput = (event: Event): void => {
	state.inputValue = event.target?.value || '';

	if (props.remote) {
		debouncedOnInputChange();
	} else {
		return onInputChange();
	}
};

const debouncedOnInputChange = lodashDebounce(() => {
	onInputChange();
}, debounce.value);

const emitChange = (val: string | number | boolean | object | (string | number | boolean | object)[] | undefined): void => {
	if (!isEqual(props.modelValue, val)) {
		emit(CHANGE_EVENT, val);
	}
};

const handleDeletePrevTag = (e: KeyboardEvent): void => {
	if (!props.multiple) {
		return;
	}

	if (e.code === EVENT_CODE.delete) {
		return;
	}

	if (e.target?.value.length <= 0) {
		const value = ((props.modelValue as Array<string | number | boolean | object> | undefined) || []).slice();
		const lastNotDisabledIndex = findLastIndex(value, (it) => !state.disabledOptions.has(it));

		if (lastNotDisabledIndex < 0) {
			return;
		}

		value.splice(lastNotDisabledIndex, 1);

		emit(UPDATE_MODEL_EVENT, value);

		emitChange(value);
	}
};

const handleDeleteTag = (event: Event, tag: SelectOption): void => {
	const index = (state.selected as SelectOption[]).indexOf(tag);

	if (index > -1 && !selectDisabled.value) {
		const value = ((props.modelValue as Array<string | number | boolean | object> | undefined) || []).slice();

		value.splice(index, 1);

		emit(UPDATE_MODEL_EVENT, value);

		emitChange(value);

		emit('remove-tag', tag.value as string | number | boolean | object);
	}

	event.stopPropagation();

	focus();
};

const handleOptionSelect = (option: OptionProxy): void => {
	if (props.multiple) {
		const value = ((props.modelValue as Array<string | number | boolean | object> | undefined) || []).slice();
		const optionIndex = getValueIndex(value, option.value);

		if (optionIndex > -1) {
			value.splice(optionIndex, 1);
		} else if (props.multipleLimit <= 0 || value.length < props.multipleLimit) {
			value.push(option.value);
		}

		emit(UPDATE_MODEL_EVENT, value);

		emitChange(value);

		if (option.created) {
			handleQueryChange('');
		}

		if (props.filterable && !props.reserveKeyword) {
			state.inputValue = '';
		}
	} else {
		emit(UPDATE_MODEL_EVENT, option.value);

		emitChange(option.value);

		expanded.value = false;
	}

	focus();

	if (expanded.value) {
		return;
	}

	nextTick(() => {
		scrollToOption(option);
	});
};

const getValueIndex = (arr: (string | number | boolean | object)[] = [], value: string | number | boolean | object): number => {
	if (!isObject(value)) {
		return arr.indexOf(value);
	}

	const valueKey = props.valueKey;

	let index = -1;

	arr.some((item, i) => {
		if (toRaw(get(item, valueKey)) === get(value, valueKey)) {
			index = i;
			return true;
		}

		return false;
	});

	return index;
};

const scrollToOption = (option: OptionProxy | SelectOption): void => {
	const targetOption = option;

	let target = null;

	if (targetOption?.value) {
		const options = optionsArray.value.filter((item) => item.value === targetOption.value);

		if (options.length > 0) {
			target = options[0].$el;
		}
	}

	if (tooltipRef.value && target) {
		const menu = tooltipRef.value?.popperRef?.contentRef?.querySelector?.(`.${nsSelect.be('dropdown', 'wrap')}`);

		if (menu) {
			scrollIntoView(menu as HTMLElement, target);
		}
	}

	scrollbarRef.value?.handleScroll();
};

const onOptionCreate = (vm: OptionProxy): void => {
	state.options.set(vm.value, vm);
	state.cachedOptions.set(vm.value, vm);

	vm.disabled && state.disabledOptions.set(vm.value, vm);
};

const onOptionDestroy = (key: string | number | boolean | object, vm: OptionProxy): void => {
	if (state.options.get(key) === vm) {
		state.options.delete(key);
	}
};

const isComposing = ref<boolean>(false);

const handleCompositionStart = (): void => {
	isComposing.value = true;
};

const handleCompositionUpdate = (): void => {
	isComposing.value = true;
};

const handleCompositionEnd = (event: CompositionEvent): void => {
	if (isComposing.value) {
		isComposing.value = false;

		onInput(event);
	}
};

const popperRef = computed(() => {
	return tooltipRef.value?.popperRef?.contentRef;
});

const handleMenuEnter = (): void => {
	nextTick(() => {
		if (state.selected) {
			scrollToOption(isArray(state.selected) ? state.selected[0] : state.selected);
		}
	});
};

const focus = (): void => {
	inputRef.value?.focus();
};

const blur = (): void => {
	handleClickOutside(new UIEvent('click'));
};

const handleClearClick = (event: Event): void => {
	event.stopPropagation();

	const value: string | number | boolean | object | (string | number | boolean | object)[] | undefined = props.multiple ? [] : undefined;

	emit(UPDATE_MODEL_EVENT, value);

	emitChange(value);

	state.hoveringIndex = -1;
	expanded.value = false;

	emit('clear');

	focus();
};

const handleClickOutside = (event: Event): void => {
	expanded.value = false;

	if (isFocused.value) {
		const _event = new FocusEvent('focus', event);

		nextTick(() => handleBlur(_event));
	}
};

const handleEsc = (): void => {
	if (state.inputValue.length > 0) {
		state.inputValue = '';
	} else {
		expanded.value = false;
	}
};

const handleToggleMenu = (): void => {
	if (selectDisabled.value) {
		return;
	}

	if (state.menuVisibleOnFocus) {
		// controlled by automaticDropdown
		state.menuVisibleOnFocus = false;
	} else {
		expanded.value = !expanded.value;
	}
};

const handleSelectOption = (): void => {
	if (!expanded.value) {
		handleToggleMenu();
	} else {
		if (optionsArray.value[state.hoveringIndex]) {
			handleOptionSelect(optionsArray.value[state.hoveringIndex]);
		}
	}
};

const getValueKey = (item: SelectOption | OptionProxy): string => {
	return isObject(item.value) ? `${get(item.value, props.valueKey)}` : `${item.value}`;
};

const optionsAllDisabled = computed<boolean>((): boolean =>
	optionsArray.value.filter((option) => option.state.visible).every((option) => option.disabled)
);

const showTagList = computed<SelectOption[] | undefined>((): SelectOption[] | undefined => {
	if (!props.multiple) {
		return [];
	}

	return props.collapseTags
		? (isArray(state.selected) ? state.selected : []).slice(0, props.maxCollapseTags)
		: isArray(state.selected)
			? state.selected
			: state.selected
				? [state.selected]
				: [];
});

const collapseTagList = computed<SelectOption[]>((): SelectOption[] => {
	if (!props.multiple) {
		return [];
	}

	return props.collapseTags ? (isArray(state.selected) ? state.selected : []).slice(props.maxCollapseTags) : [];
});

const navigateOptions = (direction: string): void => {
	if (!expanded.value) {
		expanded.value = true;

		return;
	}

	if (state.options.size === 0 || filteredOptionsCount.value === 0) {
		return;
	}

	if (!optionsAllDisabled.value) {
		if (direction === 'next') {
			state.hoveringIndex++;

			if (state.hoveringIndex === state.options.size) {
				state.hoveringIndex = 0;
			}
		} else if (direction === 'prev') {
			state.hoveringIndex--;

			if (state.hoveringIndex < 0) {
				state.hoveringIndex = state.options.size - 1;
			}
		}

		const option = optionsArray.value[state.hoveringIndex];

		if (option.disabled === true || option.state.groupDisabled === true || !option.state.visible) {
			navigateOptions(direction);
		}

		nextTick(() => hoverOption.value && scrollToOption(hoverOption.value));
	}
};

const getGapWidth = (): number => {
	if (!selectionRef.value) {
		return 0;
	}

	const style = window.getComputedStyle(selectionRef.value);

	return Number.parseFloat(style.gap || '6px');
};

// computed style
const tagStyle = computed<StyleValue>((): StyleValue => {
	const gapWidth = getGapWidth();
	const maxWidth =
		collapseItemRef.value && props.maxCollapseTags === 1 ? state.selectionWidth - state.collapseItemWidth - gapWidth : state.selectionWidth;

	return { maxWidth: `${maxWidth}px` };
});

const collapseTagStyle = computed<StyleValue>((): StyleValue => {
	return { maxWidth: `${state.selectionWidth}px` };
});

const inputStyle = computed<StyleValue>(
	(): StyleValue => ({
		width: `${Math.max(state.calculatorWidth, MINIMUM_INPUT_WIDTH)}px`,
	})
);

if (props.multiple && !isArray(props.modelValue)) {
	emit(UPDATE_MODEL_EVENT, []);
}

if (!props.multiple && isArray(props.modelValue)) {
	emit(UPDATE_MODEL_EVENT, '');
}

useResizeObserver(selectionRef, resetSelectionWidth);
useResizeObserver(calculatorRef, resetCalculatorWidth);
useResizeObserver(menuRef, updateTooltip);
useResizeObserver(wrapperRef, updateTooltip);
useResizeObserver(tagMenuRef, updateTagTooltip);
useResizeObserver(collapseItemRef, resetCollapseItemWidth);

onMounted((): void => {
	setSelected();
});

provide(
	selectContextKey,
	reactive({
		props,
		state,
		expanded,
		selectRef,
		optionsArray,
		setSelected,
		onOptionCreate,
		onOptionDestroy,
		handleOptionSelect,
	}) as unknown as SelectContext
);

defineExpose({
	/** @description */
	focus,
	/** @description */
	blur,
});
</script>
