import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export enum ItemVariantTypes {
	DEFAULT = 'default',
	LIST = 'list',
}

export const itemVariants = [ItemVariantTypes.DEFAULT, ItemVariantTypes.LIST] as const;

export type ItemVariant = ItemVariantTypes.DEFAULT | ItemVariantTypes.LIST;

export const itemProps = buildProps({
	/**
	 * @description list item variant
	 */
	variant: {
		type: definePropType<ItemVariant>(String),
		values: itemVariants,
		default: ItemVariantTypes.DEFAULT,
	},
});

export type ItemProps = ExtractPropTypes<typeof itemProps>;

export const itemEmits = {
	click: (evt: UIEvent): boolean => evt instanceof UIEvent,
};

export type ItemEmits = typeof itemEmits;
