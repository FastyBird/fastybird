import { FormResultType } from '../../types';

export interface IResetPasswordForm {
	uid: string;
}

export interface IResetPasswordProps {
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultType;
	remoteFormReset?: boolean;
}
