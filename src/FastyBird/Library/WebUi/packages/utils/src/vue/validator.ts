import { COMPONENT_SIZES, DATE_PICK_TYPES } from '@fastybird/web-ui-constants';
import type { ComponentSize, DatePickType } from '@fastybird/web-ui-constants';

export const isValidComponentSize = (val: string): val is ComponentSize | '' => ['', ...COMPONENT_SIZES].includes(val);

export const isValidDatePickType = (val: string): val is DatePickType => ([...DATE_PICK_TYPES] as string[]).includes(val);
