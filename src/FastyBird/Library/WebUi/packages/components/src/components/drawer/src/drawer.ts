import { buildProps, definePropType, isBoolean } from '@fastybird/web-ui-utils';
import { UPDATE_MODEL_EVENT } from '@fastybird/web-ui-constants';

import { drawerContentProps } from './drawer-content';

import type { ExtractPropTypes } from 'vue';

type DoneFn = (cancel?: boolean) => void;

export type DrawerBeforeCloseFn = (done: DoneFn) => void;

export const drawerProps = buildProps({
	...drawerContentProps,
	/**
	 * @description whether to append modal itself to body. A nested modal should have this attribute set to `true`
	 */
	appendToBody: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
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
		type: definePropType<DrawerBeforeCloseFn | undefined>(Function),
		default: undefined,
	},
	/**
	 * @description destroy elements in modal when closed
	 */
	destroyOnClose: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
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
	 * @description visibility of modal
	 */
	modelValue: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
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
	 * @description same as z-index in native CSS, z-order of modal
	 */
	zIndex: {
		type: definePropType<number>(Boolean),
		default: undefined,
	},
	/**
	 * @description
	 */
	trapFocus: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	withHeader: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description
	 */
	modalFade: {
		type: definePropType<boolean>(Boolean),
		default: true,
	},
	/**
	 * @description header's aria-level attribute
	 */
	headerAriaLevel: {
		type: definePropType<string>(String),
		default: '2',
	},
} as const);

export type DrawerProps = ExtractPropTypes<typeof drawerProps>;

export const drawerEmits = {
	open: () => true,
	opened: () => true,
	close: () => true,
	closed: () => true,
	[UPDATE_MODEL_EVENT]: (value: boolean) => isBoolean(value),
	openAutoFocus: () => true,
	closeAutoFocus: () => true,
	leftClick: () => true,
	rightClick: () => true,
};

export type DrawerEmits = typeof drawerEmits;
