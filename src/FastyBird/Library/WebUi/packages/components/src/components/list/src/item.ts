import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export enum ListItemVariantTypes {
	DEFAULT = 'default',
	LIST = 'list',
}

export const listItemVariants = [ListItemVariantTypes.DEFAULT, ListItemVariantTypes.LIST] as const;

export type ListItemVariant = ListItemVariantTypes.DEFAULT | ListItemVariantTypes.LIST;

export const itemProps = buildProps({
	/**
	 * @description list item variant
	 */
	variant: {
		type: definePropType<ListItemVariant>(String),
		values: listItemVariants,
		default: ListItemVariantTypes.DEFAULT,
	},
});

export type ItemProps = ExtractPropTypes<typeof itemProps>;

export const itemEmits = {
	click: (evt: UIEvent): boolean => evt instanceof UIEvent,
};

export type ItemEmits = typeof itemEmits;
