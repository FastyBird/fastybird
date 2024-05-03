import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import { modalFooterEmits, modalFooterProps } from './modal-footer';
import { modalHeaderEmits, modalHeaderProps } from './modal-header';

import type { ExtractPropTypes } from 'vue';

export const modalContentProps = buildProps({
	...modalHeaderProps,
	...modalFooterProps,
	/**
	 * @description whether to show modal header
	 */
	showHeader: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description whether to show modal footer
	 */
	showFooter: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description whether to align the header and footer in center
	 */
	center: Boolean,
	/**
	 * @description whether to align the modal both horizontally and vertically
	 */
	alignCenter: Boolean,
	/**
	 * @description enable dragging feature for Modal
	 */
	draggable: Boolean,
	/**
	 * @description draggable Modal can overflow the viewport
	 */
	overflow: Boolean,
	/**
	 * @description whether the Modal takes up full screen
	 */
	fullscreen: Boolean,
	/**
	 * @description
	 */
	closable: Boolean,
} as const);

export type ModalContentProps = ExtractPropTypes<typeof modalContentProps>;

export const modalContentEmits = {
	...modalHeaderEmits,
	...modalFooterEmits,
};

export type ModalContentEmits = typeof modalContentEmits;
