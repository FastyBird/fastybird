export enum EffectTypes {
	DARK = 'dark',
	LIGHT = 'light',
	PLAIN = 'plain',
}

export const EFFECTS = ['', EffectTypes.LIGHT, EffectTypes.DARK, EffectTypes.PLAIN] as const;

export type Effect = (typeof EFFECTS)[number];
