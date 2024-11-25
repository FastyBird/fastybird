import { FormResultType } from '../../types';

export interface ISignInForm {
	uid: string;
	password: string;
	persistent: boolean;
}

export interface ISignInProps {
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultType;
	remoteFormReset?: boolean;
}
