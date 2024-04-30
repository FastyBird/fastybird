export enum EffectTypes {
	DARK = 'dark',
	LIGHT = 'light',
	PLAIN = 'plain',
}

export const EFFECTS = ['', EffectTypes.LIGHT, EffectTypes.DARK, EffectTypes.PLAIN];

export type Effect = (typeof EFFECTS)[number];
