import { ensureArray } from '@fastybird/web-ui-utils';

import { FormItemContext, FormItemProp } from '../../form-item';

import type { Arrayable } from '@fastybird/web-ui-utils';

export const filterFields = (fields: FormItemContext[], props: Arrayable<FormItemProp>) => {
	const normalized = ensureArray(props);
	return normalized.length > 0 ? fields.filter((field) => field.prop && normalized.includes(field.prop)) : fields;
};
