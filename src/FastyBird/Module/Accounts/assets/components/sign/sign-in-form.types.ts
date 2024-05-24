import { FormResultTypes } from '../../types';

export interface ISignInForm {
	uid: string;
	password: string;
	persistent: boolean;
}

export interface ISignInProps {
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultTypes;
	remoteFormReset?: boolean;
}
