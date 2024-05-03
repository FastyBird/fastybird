import { unref } from 'vue';

import { isArray } from '@fastybird/web-ui-utils';

import type { Ref } from 'vue';
import type { Arrayable } from '@fastybird/web-ui-utils';
import type { TooltipTriggerType } from './trigger';

export const isTriggerType = (trigger: Arrayable<TooltipTriggerType>, type: TooltipTriggerType): boolean => {
	if (isArray(trigger)) {
		return trigger.includes(type);
	}

	return trigger === type;
};

export const whenTrigger = (
	trigger: Ref<Arrayable<TooltipTriggerType>>,
	type: TooltipTriggerType,
	handler: (e: Event) => void
): ((e: Event) => void) => {
	return (e: Event): void => {
		isTriggerType(unref(trigger), type) && handler(e);
	};
};
