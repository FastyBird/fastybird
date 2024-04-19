import { FormEmits, FormProps } from './form';
import { FormItemContext, FormItemRule, FormLabelWidthContext, FormValidateCallback, FormValidationResult } from '../../form-item';

import type { ValidateError, ValidateFieldsError } from 'async-validator';
import type { SetupContext, UnwrapRef } from 'vue';
import type { MaybeRef } from '@vueuse/core';
import type { Arrayable } from '@fastybird/web-ui-utils';
import type { FormItemProp } from '../../form-item';

type Primitive = null | undefined | string | number | boolean | symbol | bigint;

type BrowserNativeObject = Date | FileList | File | Blob | RegExp;

/**
 * Array method key
 */
type ArrayMethodKey = keyof any[];

/**
 * Helper type for recursively constructing paths through a type
 */
type PathImpl<K extends string | number, V> = V extends Primitive | BrowserNativeObject ? `${K}` : `${K}` | `${K}.${Path<V>}`;

/**
 * Check whether it is tuple
 *
 * @example
 * IsTuple<[1, 2, 3]> => true
 * IsTuple<Array[number]> => false
 */
type IsTuple<T extends ReadonlyArray<any>> = number extends T['length'] ? false : true;

/**
 * Tuple index key
 *
 * @example
 * TupleKey<[1, 2, 3]> => '0' | '1' | '2'
 */
type TupleKey<T extends ReadonlyArray<any>> = Exclude<keyof T, ArrayMethodKey>;

/**
 * Array index key
 */
type ArrayKey = number;

/**
 * Type which collects all paths through a type
 *
 * @see {@link FieldPath}
 */
type Path<T> =
	T extends ReadonlyArray<infer V>
		? IsTuple<T> extends true
			? {
					[K in TupleKey<T>]-?: PathImpl<Exclude<K, symbol>, T[K]>;
				}[TupleKey<T>] // tuple
			: PathImpl<ArrayKey, V> // array
		: {
				[K in keyof T]-?: PathImpl<Exclude<K, symbol>, T[K]>;
			}[keyof T]; // object

/**
 * Type which collects all paths through a type
 *
 * @example
 * FieldPath<{ 1: number; a: number; b: string; c: { d: number; e: string }; f: [{ value: string }]; g: { value: string }[]; h: Date; i: FileList; j: File; k: Blob; l: RegExp }> => '1' | 'a' | 'b' | 'c' | 'f' | 'g' | 'c.d' | 'c.e' | 'f.0' | 'f.0.value' | 'g.number' | 'g.number.value' | 'h' | 'i' | 'j' | 'k' | 'l'
 */
type FieldPath<T> = T extends object ? Path<T> : never;

export type FormRules<T extends MaybeRef<Record<string, any> | string> = string> = Partial<
	Record<UnwrapRef<T> extends string ? UnwrapRef<T> : FieldPath<UnwrapRef<T>>, Arrayable<FormItemRule>>
>;

export interface FormValidateFailure {
	errors: ValidateError[] | null;
	fields: ValidateFieldsError;
}

export type FormContext = FormProps &
	UnwrapRef<FormLabelWidthContext> & {
		emit: SetupContext<FormEmits>['emit'];
		getField: (prop: string) => FormItemContext | undefined;
		addField: (field: FormItemContext) => void;
		removeField: (field: FormItemContext) => void;
		resetFields: (props?: Arrayable<FormItemProp>) => void;
		clearValidate: (props?: Arrayable<FormItemProp>) => void;
		validateField: (props?: Arrayable<FormItemProp>, callback?: FormValidateCallback) => FormValidationResult;
	};
