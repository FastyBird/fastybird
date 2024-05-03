import { buildProps, definePropType } from '@fastybird/web-ui-utils';
import { TypeComponentsMap } from '@fastybird/web-ui-utils';

import { NotAllowed, NotFound, ServerError } from './icons';

import type { Component, ExtractPropTypes } from 'vue';

export enum ResultIconTypes {
	SUCCESS = 'success',
	WARNING = 'warning',
	INFO = 'info',
	ERROR = 'error',
	NOT_ALLOWED = '403',
	NOT_FOUND = '404',
	SERVER_ERROR = '500',
}

export const resultIcons = [
	ResultIconTypes.SUCCESS,
	ResultIconTypes.WARNING,
	ResultIconTypes.INFO,
	ResultIconTypes.ERROR,
	ResultIconTypes.NOT_ALLOWED,
	ResultIconTypes.NOT_FOUND,
	ResultIconTypes.SERVER_ERROR,
] as const;

export type ResultIcon =
	| ResultIconTypes.SUCCESS
	| ResultIconTypes.WARNING
	| ResultIconTypes.INFO
	| ResultIconTypes.ERROR
	| ResultIconTypes.NOT_ALLOWED
	| ResultIconTypes.NOT_FOUND
	| ResultIconTypes.SERVER_ERROR;

export const IconMap = {
	success: 'icon-success',
	warning: 'icon-warning',
	error: 'icon-error',
	info: 'icon-info',
	'403': 'icon-403',
	'404': 'icon-404',
	'500': 'icon-500',
} as const;

export const IconComponentMap: Record<(typeof IconMap)[keyof typeof IconMap], Component> = {
	[IconMap.success]: TypeComponentsMap.success,
	[IconMap.warning]: TypeComponentsMap.warning,
	[IconMap.error]: TypeComponentsMap.error,
	[IconMap.info]: TypeComponentsMap.info,
	[IconMap['403']]: NotAllowed,
	[IconMap['404']]: NotFound,
	[IconMap['500']]: ServerError,
};

export const resultProps = buildProps({
	/**
	 * @description title of result
	 */
	title: {
		type: definePropType<string>(String),
		default: '',
	},
	/**
	 * @description subtitle of result
	 */
	subtitle: {
		type: definePropType<string>(String),
		default: '',
	},
	/**
	 * @description icon type of result
	 */
	status: {
		type: definePropType<ResultIcon>(String),
		values: resultIcons,
		default: ResultIconTypes.INFO,
	},
	/**
	 * @description
	 */
	animation: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
} as const);

export type ResultProps = ExtractPropTypes<typeof resultProps>;
