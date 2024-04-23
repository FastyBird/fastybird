import { warn } from 'vue';
import { hasOwn } from 'vue';
import { fromPairs } from 'lodash-unified';

import { isObject } from '../../types';

import type { PropType } from 'vue';
import type { FbProp, FbPropConvert, FbPropFinalized, FbPropInput, FbPropMergeType, IfFbProp, IfNativePropType, NativePropType } from './types';

export const fbPropKey = '__fbPropKey';

export const definePropType = <T>(val: any): PropType<T> => val;

export const isFbProp = (val: unknown): val is FbProp<any, any, any> => isObject(val) && !!(val as any)[fbPropKey];

/**
 * @description Build prop. It can better optimize prop types
 * @example
  // limited options
  // the type will be PropType<'light' | 'dark'>
  buildProp({
    type: String,
    values: ['light', 'dark'],
  } as const)
  * @example
  // limited options and other types
  // the type will be PropType<'small' | 'large' | number>
  buildProp({
    type: [String, Number],
    values: ['small', 'large'],
    validator: (val: unknown): val is number => typeof val === 'number',
  } as const)
 */
export const buildProp = <
	Type = never,
	Value = never,
	Validator = never,
	Default extends FbPropMergeType<Type, Value, Validator> = never,
	Required extends boolean = false,
>(
	prop: FbPropInput<Type, Value, Validator, Default, Required>,
	key?: string
): FbPropFinalized<Type, Value, Validator, Default, Required> => {
	// filter native prop type and nested prop, e.g `null`, `undefined` (from `buildProps`)
	if (!isObject(prop) || isFbProp(prop)) return prop as any;

	const { values, required, default: defaultValue, type, validator } = prop;

	const _validator =
		values || validator
			? (val: unknown): boolean => {
					let valid = false;
					let allowedValues: unknown[] = [];

					if (values) {
						allowedValues = Array.from(values);

						if (hasOwn(prop, 'default')) {
							allowedValues.push(defaultValue);
						}

						valid ||= allowedValues.includes(val);
					}

					if (validator) {
						valid ||= validator(val);
					}

					if (!valid && allowedValues.length > 0) {
						const allowValuesText = [...new Set(allowedValues)].map((value) => JSON.stringify(value)).join(', ');

						warn(
							`Invalid prop: validation failed${key ? ` for prop "${key}"` : ''}. Expected one of [${allowValuesText}], got value ${JSON.stringify(
								val
							)}.`
						);
					}

					return valid;
				}
			: undefined;

	const fbProp: any = {
		type,
		required: !!required,
		validator: _validator,
		[fbPropKey]: true,
	};

	if (hasOwn(prop, 'default')) {
		fbProp.default = defaultValue;
	}

	return fbProp;
};

export const buildProps = <Props extends Record<string, { [fbPropKey]: true } | NativePropType | FbPropInput<any, any, any, any, any>>>(
	props: Props
): {
	[K in keyof Props]: IfFbProp<Props[K], Props[K], IfNativePropType<Props[K], Props[K], FbPropConvert<Props[K]>>>;
} => fromPairs(Object.entries(props).map(([key, option]) => [key, buildProp(option as any, key)])) as any;
