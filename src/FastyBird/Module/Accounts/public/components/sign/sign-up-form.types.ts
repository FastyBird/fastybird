import { FbFormResultTypes } from '@fastybird/web-ui-library';

export interface ISignUpForm {
	emailAddress: string;
	firstName: string;
	lastName: string;
	password: string;
}

export interface ISignUpProps {
	remoteFormSubmit?: boolean;
	remoteFormResult?: FbFormResultTypes;
	remoteFormReset?: boolean;
}
