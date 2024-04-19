export enum VariantTypes {
	DEFAULT = 'default',
	PRIMARY = 'primary',
	SUCCESS = 'success',
	ERROR = 'error',
	DANGER = 'danger',
	WARNING = 'warning',
	INFO = 'info',
}

export const VARIANTS = [
	'',
	VariantTypes.DEFAULT,
	VariantTypes.PRIMARY,
	VariantTypes.INFO,
	VariantTypes.SUCCESS,
	VariantTypes.WARNING,
	VariantTypes.DANGER,
	VariantTypes.ERROR,
] as const;

export type Variant = (typeof VARIANTS)[number];
