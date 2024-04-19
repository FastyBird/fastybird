import { computed, ComputedRef, inject, ref, unref } from 'vue';

import { ComponentSizeTypes } from '@fastybird/web-ui-constants';
import { useGlobalSize, useProp } from '@fastybird/web-ui-hooks';

import { formItemContextKey } from '../../../form-item';
import { formContextKey } from '../constants';

import type { MaybeRef } from '@vueuse/core';
import type { ComponentSize } from '@fastybird/web-ui-constants';

export const useFormSize = (
	fallback?: MaybeRef<ComponentSize | undefined>,
	ignore: Partial<Record<'prop' | 'form' | 'formItem' | 'global', boolean>> = {}
): ComputedRef<ComponentSize> => {
	const emptyRef = ref(undefined);

	const size = ignore.prop ? emptyRef : useProp<ComponentSize>('size');
	const globalConfig = ignore.global ? emptyRef : useGlobalSize();
	const form = ignore.form ? { size: undefined } : inject(formContextKey, undefined);
	const formItem = ignore.formItem ? { size: undefined } : inject(formItemContextKey, undefined);

	return computed<ComponentSize>(
		(): ComponentSize => size.value || unref(fallback) || formItem?.size || form?.size || globalConfig.value || ComponentSizeTypes.DEFAULT
	);
};

export const useFormDisabled = (fallback?: MaybeRef<boolean | undefined>): ComputedRef<boolean> => {
	const disabled = useProp<boolean>('disabled');
	const form = inject(formContextKey, undefined);

	return computed<boolean>(() => disabled.value || unref(fallback) || form?.disabled || false);
};
