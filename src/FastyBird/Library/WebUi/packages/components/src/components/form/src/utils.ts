import { ensureArray } from '@fastybird/web-ui-utils';

import type { Arrayable } from '@fastybird/web-ui-utils';
import type { FormItemContext, FormItemProp } from '../../form-item';

export const filterFields = (fields: FormItemContext[], props: Arrayable<FormItemProp>): FormItemContext[] => {
	const normalized = ensureArray(props);
	return normalized.length > 0 ? fields.filter((field) => field.prop && normalized.includes(field.prop)) : fields;
};
