import { buildProps, definePropType, isBoolean } from '@fastybird/web-ui-utils';
import { UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';

import { modalContentProps } from './modal-content';

import type { ExtractPropTypes } from 'vue';

type DoneFn = (cancel?: boolean) => void;

export type ModalBeforeCloseFn = (done: DoneFn) => void;

export const modalProps = buildProps({
	...modalContentProps,
	/**
	 * @description whether to append modal itself to body. A nested modal should have this attribute set to `true`
	 */
	appendToBody: Boolean,
	/**
	 * @description which element the modal appends to
	 */
	appendTo: {
		type: definePropType<string>(String),
		default: 'body',
	},
	/**
	 * @description callback before modal closes, and it will prevent modal from closing, use done to close the modal
	 */
	beforeClose: {
		type: definePropType<ModalBeforeCloseFn>(Function),
	},
	/**
	 * @description destroy elements in modal when closed
	 */
	destroyOnClose: Boolean,
	/**
	 * @description whether the modal can be closed by clicking the mask
	 */
	closeOnClickBackdrop: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description whether the modal can be closed by pressing ESC
	 */
	closeOnPressEscape: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description whether scroll of body is disabled while modal is displayed
	 */
	lockScroll: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description whether a mask is displayed
	 */
	backdrop: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description the Time(milliseconds) before open
	 */
	openDelay: {
		type: definePropType<number>(Number),
		default: 0,
	},
	/**
	 * @description the Time(milliseconds) before close
	 */
	closeDelay: {
		type: definePropType<number>(Number),
		default: 0,
	},
	/**
	 * @description value for `margin-top` of modal CSS, default is 15vh
	 */
	top: {
		type: String,
	},
	/**
	 * @description visibility of modal
	 */
	modelValue: Boolean,
	/**
	 * @description whether the modal could be closed by close action
	 */
	closable: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description custom class names for mask
	 */
	customClass: {
		type: definePropType<string | string[] | Record<string, boolean> | undefined>([String, Array, Object]),
		default: undefined,
	},
	/**
	 * @description width of modal, default is 50%
	 */
	width: {
		type: definePropType<string | number>([String, Number]),
	},
	/**
	 * @description same as z-index in native CSS, z-order of modal
	 */
	zIndex: Number,
	/**
	 * @description
	 */
	trapFocus: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description header's aria-level attribute
	 */
	headerAriaLevel: {
		type: definePropType<string>(String),
		default: '2',
	},
} as const);

export type ModalProps = ExtractPropTypes<typeof modalProps>;

export const modalEmits = {
	open: (): boolean => true,
	opened: (): boolean => true,
	close: (): boolean => true,
	closed: (): boolean => true,
	[UPDATE_MODEL_EVENT]: (value: boolean): boolean => isBoolean(value),
	openAutoFocus: (): boolean => true,
	closeAutoFocus: (): boolean => true,
	leftClick: (): boolean => true,
	rightClick: (): boolean => true,
};

export type ModalEmits = typeof modalEmits;
