import type { InjectionKey } from 'vue';

import type { FormItemContext } from './types';

export const formItemContextKey: InjectionKey<FormItemContext> = Symbol('formItemContextKey');
