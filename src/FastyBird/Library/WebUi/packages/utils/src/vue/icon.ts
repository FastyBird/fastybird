import {
	FarCircleCheck,
	FasCircleCheck,
	FarCircleXmark,
	FasCircleXmark,
	FasXmark,
	FasCircleInfo,
	FasSpinner,
	FasCircleExclamation,
	FasAngleUp,
	FasAngleDown,
	FasAngleRight,
	FasAngleLeft,
} from '@fastybird/web-ui-icons';

export const CloseComponents = {
	FasXmark,
};

export const CloseComponentsMap = {
	close: FasXmark,
};

export const TypeComponents = {
	FasXmark,
	FasCircleCheck,
	FasCircleInfo,
	FasCircleExclamation,
	FasCircleXmark,
};

export const TypeComponentsMap = {
	success: FasCircleCheck,
	warning: FasCircleExclamation,
	error: FasCircleXmark,
	info: FasCircleInfo,
};

export const ValidateComponentsMap = {
	validating: FasSpinner,
	success: FarCircleCheck,
	error: FarCircleXmark,
};

export const ArrowComponentsMap = {
	up: FasAngleUp,
	right: FasAngleRight,
	down: FasAngleDown,
	left: FasAngleLeft,
};
