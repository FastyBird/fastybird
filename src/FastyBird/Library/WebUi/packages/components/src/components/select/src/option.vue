<template>
	<li
		v-show="state.visible"
		:id="id"
		:class="containerClass"
		:aria-disabled="isDisabled || undefined"
		:aria-selected="itemSelected"
		role="option"
		@mouseenter="handleHover"
		@click.stop="handleClick"
	>
		<slot>
			<span>{{ currentLabel }}</span>
		</slot>
	</li>
</template>

<script lang="ts">
import { computed, getCurrentInstance, inject, toRaw, watch, nextTick, onBeforeUnmount, defineComponent, reactive } from 'vue';
import { get, isEqual } from 'lodash';

import { useId, useNamespace } from '@fastybird/web-ui-hooks';
import { escapeStringRegexp, isObject } from '@fastybird/web-ui-utils';

import { optionProps } from './option';
import { selectGroupContextKey, selectContextKey } from './constants';

import type { OptionProps, OptionProxy } from './option';

export default defineComponent({
	name: 'FbOption',

	componentName: 'FbOption',

	props: optionProps,

	setup(props: OptionProps) {
		const ns = useNamespace('select');
		const id = useId();

		const select = inject(selectContextKey);
		const selectGroup = inject(selectGroupContextKey, { disabled: false });

		const instance = getCurrentInstance();

		const vm = instance!.proxy! as unknown as OptionProxy;

		select!.onOptionCreate(vm);

		const containerClass = computed<string[]>((): string[] => [
			ns.be('dropdown', 'item'),
			ns.is('disabled', isDisabled.value),
			ns.is('selected', itemSelected.value),
			ns.is('hovering', state.hover),
		]);

		const state = reactive<OptionProxy['state']>({
			index: -1,
			groupDisabled: false,
			visible: true,
			hover: false,
		});

		const itemSelected = computed<boolean>((): boolean => {
			if (select!.props.multiple) {
				return contains(select!.props.modelValue as (string | number | boolean | object)[], props.value);
			} else {
				return contains([select!.props.modelValue] as (string | number | boolean | object)[], props.value);
			}
		});

		const limitReached = computed<boolean>((): boolean => {
			if (select!.props.multiple) {
				const modelValue = (select!.props.modelValue || []) as (string | number | boolean | object)[];

				return !itemSelected.value && modelValue.length >= select!.props.multipleLimit && select!.props.multipleLimit > 0;
			} else {
				return false;
			}
		});

		const currentLabel = computed<string | number | boolean>((): string | number | boolean => {
			return props.label || (isObject(props.value) ? '' : (props.value as string | number | boolean));
		});

		const currentValue = computed<string | number | boolean | object>((): string | number | boolean | object => {
			return props.value || props.label || '';
		});

		const isDisabled = computed<boolean>((): boolean => {
			return props.disabled || state.groupDisabled || limitReached.value;
		});

		const contains = (arr: (string | number | boolean | object)[] = [], target: string | number | boolean | object): boolean => {
			if (!isObject(props.value)) {
				return arr && arr.includes(target);
			} else {
				const valueKey = select!.props.valueKey || 'value';

				return (
					arr &&
					arr.some((item) => {
						return toRaw(get(item, valueKey)) === get(target, valueKey);
					})
				);
			}
		};

		const handleHover = (): void => {
			if (!props.disabled && !selectGroup.disabled) {
				select!.state.hoveringIndex = select!.optionsArray.indexOf(vm);
			}
		};

		const handleClick = (): void => {
			if (props.disabled !== true && state.groupDisabled !== true) {
				select!.handleOptionSelect(vm);
			}
		};

		const updateOption = (query: string): void => {
			const regexp = new RegExp(escapeStringRegexp(query), 'i');

			state.visible = regexp.test(`${currentLabel.value}`) || props.created;
		};

		onBeforeUnmount((): void => {
			const key = props.value;

			const selectedOptions = select!.props.multiple
				? (select!.state.selected as (string | number | boolean | object)[])
				: select!.state.selected
					? [select!.state.selected]
					: [];

			const doesSelected = selectedOptions.some((item: string | number | boolean | object) => {
				return item === props.value;
			});

			// if option is not selected, remove it from cache
			nextTick((): void => {
				if (select!.state.cachedOptions.get(key) === vm && !doesSelected) {
					select!.state.cachedOptions.delete(key);
				}
			});

			select!.onOptionDestroy(key, vm);
		});

		watch(
			(): string | number | boolean => currentLabel.value,
			(): void => {
				if (!props.created && !select!.props.remote) {
					select!.setSelected();
				}
			}
		);

		watch(
			(): string | number | boolean | object => props.value,
			(val: string | number | boolean | object, oldVal: string | number | boolean | object): void => {
				const { remote, valueKey } = select!.props;

				if (!isEqual(val, oldVal)) {
					select!.onOptionDestroy(oldVal, vm);
					select!.onOptionCreate(vm);
				}

				if (!props.created && !remote) {
					if (valueKey && isObject(val) && isObject(oldVal) && val[valueKey] === oldVal[valueKey]) {
						return;
					}

					select!.setSelected();
				}
			}
		);

		watch(
			(): boolean => selectGroup.disabled,
			(): void => {
				state.groupDisabled = selectGroup.disabled;
			},
			{ immediate: true }
		);

		return {
			id,
			ns,
			containerClass,
			state,
			itemSelected,
			limitReached,
			currentLabel,
			currentValue,
			isDisabled,
			select,
			updateOption,
			handleHover,
			handleClick,
		};
	},
});
</script>
