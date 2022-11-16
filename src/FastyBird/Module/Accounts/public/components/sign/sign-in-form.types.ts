import { FbFormResultTypes } from '@fastybird/web-ui-library';

export interface ISignInForm {
	uid: string;
	password: string;
	persistent: boolean;
}

export interface ISignInProps {
	remoteFormSubmit?: boolean;
	remoteFormResult?: FbFormResultTypes;
	remoteFormReset?: boolean;
}
