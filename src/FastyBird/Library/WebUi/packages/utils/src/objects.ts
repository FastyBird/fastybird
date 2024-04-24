import { get, set } from 'lodash';

import type { Entries } from 'type-fest';
import type { Arrayable } from '.';

export const keysOf = <T>(arr: T): Array<keyof T> => Object.keys(arr as any) as Array<keyof T>;
export const entriesOf = <T>(arr: T): Entries<T> => Object.entries(arr as any) as Entries<T>;
export { hasOwn } from '@vue/shared';

export const getProp = <T = any>(obj: Record<string, any>, path: Arrayable<string>, defaultValue?: any): { value: T } => {
	return {
		get value(): any {
			return get(obj, path, defaultValue);
		},
		set value(val: any) {
			set(obj, path, val);
		},
	};
};
