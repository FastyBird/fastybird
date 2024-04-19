import type { InjectionKey } from 'vue';

import type { FormContext } from './types';

export const formContextKey: InjectionKey<FormContext> = Symbol('formContextKey');
