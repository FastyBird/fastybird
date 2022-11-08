<template>
	<div :class="classNames">
		<slot />
	</div>
</template>

<script lang="ts">
import { computed, defineComponent, PropType } from 'vue';
import get from 'lodash/get';

import { FbSizeTypes, FbUiTextAlignTypes } from '@/types';

import { IFbUiContentProps } from './types';

const sizeValidator = (value: FbSizeTypes): boolean => {
	// The value must match one of these strings
	return [FbSizeTypes.LARGE, FbSizeTypes.MEDIUM, FbSizeTypes.SMALL, FbSizeTypes.EXTRA_SMALL, FbSizeTypes.NONE].includes(value);
};

export default defineComponent({
	name: 'FbUiContent',

	props: {
		mt: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.NONE,
			validator: sizeValidator,
		},

		mb: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.NONE,
			validator: sizeValidator,
		},

		ml: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.NONE,
			validator: sizeValidator,
		},

		mr: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.NONE,
			validator: sizeValidator,
		},

		mv: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.NONE,
			validator: sizeValidator,
		},

		mh: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.NONE,
			validator: sizeValidator,
		},

		pt: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.NONE,
			validator: sizeValidator,
		},

		pb: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.NONE,
			validator: sizeValidator,
		},

		pl: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.NONE,
			validator: sizeValidator,
		},

		pr: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.NONE,
			validator: sizeValidator,
		},

		pv: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.NONE,
			validator: sizeValidator,
		},

		ph: {
			type: String as PropType<FbSizeTypes>,
			default: FbSizeTypes.NONE,
			validator: sizeValidator,
		},

		textAlign: {
			type: String as PropType<FbUiTextAlignTypes>,
			default: FbUiTextAlignTypes.NONE,
			validator: (value: FbUiTextAlignTypes): boolean => {
				// The value must match one of these strings
				return [
					FbUiTextAlignTypes.NONE,
					FbUiTextAlignTypes.LEFT,
					FbUiTextAlignTypes.RIGHT,
					FbUiTextAlignTypes.CENTER,
					FbUiTextAlignTypes.JUSTIFY,
				].includes(value);
			},
		},
	},

	setup(props: IFbUiContentProps) {
		const classNames = computed<string[]>((): string[] => {
			const values: string[] = ['fb-theme-ui-content'];

			const margins = ['mt', 'mb', 'ml', 'mr', 'mv', 'mh'];
			const paddings = ['pt', 'pb', 'pl', 'pr', 'pv', 'ph'];

			Object.keys(props).forEach((key: string): void => {
				if (margins.includes(key) && get(props, key) !== FbSizeTypes.NONE) {
					values.push(`fb-theme-ui-content-${key}-${get(props, key)}`);
				}

				if (paddings.includes(key) && get(props, key) !== FbSizeTypes.NONE) {
					values.push(`fb-theme-ui-content-${key}-${get(props, key)}`);
				}
			});

			if (props.textAlign !== FbUiTextAlignTypes.NONE) {
				values.push(`fb-theme-ui-content-text-align-${props.textAlign}`);
			}

			return values;
		});

		return {
			classNames,
		};
	},
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'index';
</style>
