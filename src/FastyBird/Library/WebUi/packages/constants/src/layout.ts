export enum LayoutTypes {
	DEFAULT = 'default',
	PHONE = 'phone',
	TABLET = 'tablet',
}

export const LAYOUTS = ['', LayoutTypes.DEFAULT, LayoutTypes.PHONE, LayoutTypes.TABLET] as const;

export type Layout = (typeof LAYOUTS)[number];
