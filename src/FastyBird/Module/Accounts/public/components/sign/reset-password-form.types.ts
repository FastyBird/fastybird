import { FbFormResultTypes } from '@fastybird/web-ui-library';

export interface IResetPasswordForm {
	uid: string;
}

export interface IResetPasswordProps {
	remoteFormSubmit?: boolean;
	remoteFormResult?: FbFormResultTypes;
	remoteFormReset?: boolean;
}
