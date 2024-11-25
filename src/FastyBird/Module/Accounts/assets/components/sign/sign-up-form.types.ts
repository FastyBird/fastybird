import { FormResultType } from '../../types';

export interface ISignUpForm {
	emailAddress: string;
	firstName: string;
	lastName: string;
	password: string;
}

export interface ISignUpProps {
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultType;
	remoteFormReset?: boolean;
}
