export enum ComponentSizeTypes {
	DEFAULT = 'default',
	SMALL = 'small',
	LARGE = 'large',
}

export const COMPONENT_SIZES = [ComponentSizeTypes.DEFAULT, ComponentSizeTypes.SMALL, ComponentSizeTypes.LARGE] as const;

export type ComponentSize = (typeof COMPONENT_SIZES)[number];

export const componentSizeMap = {
	large: 40,
	default: 32,
	small: 24,
} as const;
