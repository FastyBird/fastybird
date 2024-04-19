import { buildProps } from '@fastybird/web-ui-utils';

import { radioPropsBase } from './radio';

import type { ExtractPropTypes } from 'vue';

export const radioButtonProps = buildProps({
	...radioPropsBase,
} as const);

export type RadioButtonProps = ExtractPropTypes<typeof radioButtonProps>;
