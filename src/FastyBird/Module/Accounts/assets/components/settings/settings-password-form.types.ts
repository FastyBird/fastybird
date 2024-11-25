import { FormResultType, IIdentity, LayoutType } from '../../types';

export interface ISettingsPasswordProps {
	identity: IIdentity;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultType;
	remoteFormReset?: boolean;
	layout?: LayoutType;
}

export interface ISettingsPasswordForm {
	currentPassword: string;
	newPassword: string;
	repeatPassword: string;
}
