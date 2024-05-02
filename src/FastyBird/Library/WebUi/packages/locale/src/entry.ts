export { default as en } from './lang/en';
export { default as cs } from './lang/cs';

export type TranslatePair = {
	[key: string]: string | string[] | TranslatePair;
};

export type Language = {
	name: string;
	component: TranslatePair;
};
