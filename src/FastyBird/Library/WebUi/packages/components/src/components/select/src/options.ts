import { defineComponent, inject } from 'vue';
import { isEqual } from 'lodash-unified';

import { isArray } from '@vue/shared';
import { isFunction, isString } from '@fastybird/web-ui-utils';

import { selectContextKey } from './constants';

import type { Component, VNode, VNodeNormalizedChildren } from 'vue';

export default defineComponent({
	name: 'FbOptions',

	setup(_, { slots }) {
		const select = inject(selectContextKey);

		let cachedValueList: (string | number | boolean | object)[] = [];

		return () => {
			const children = slots.default?.()!;
			const valueList: (string | number | boolean | object)[] = [];

			const filterOptions = (children?: VNodeNormalizedChildren): void => {
				if (!isArray(children)) {
					return;
				}

				(children as VNode[]).forEach((item) => {
					const name = ((item?.type || {}) as Component)?.name;

					if (name === 'FbOptionGroup') {
						filterOptions(
							!isString(item.children) && !isArray(item.children) && isFunction(item.children?.default) ? item.children?.default() : item.children
						);
					} else if (name === 'FbOption') {
						valueList.push(item.props?.value);
					} else if (isArray(item.children)) {
						filterOptions(item.children);
					}
				});
			};

			if (children.length) {
				filterOptions(children![0]?.children);
			}

			if (!isEqual(valueList, cachedValueList)) {
				cachedValueList = valueList;

				if (select) {
					select.state.optionValues = valueList;
				}
			}

			return children;
		};
	},
});
