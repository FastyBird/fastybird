import { FormResultTypes } from '../../types';

export interface IResetPasswordForm {
	uid: string;
}

export interface IResetPasswordProps {
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultTypes;
	remoteFormReset?: boolean;
}
