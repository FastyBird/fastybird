import { IIdentity } from '../../models/identities/types';
import { FormResultTypes, LayoutType } from '../../types';

export interface ISettingsPasswordProps {
	identity: IIdentity;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultTypes;
	remoteFormReset?: boolean;
	layout?: LayoutType;
}

export interface ISettingsPasswordForm {
	currentPassword: string;
	newPassword: string;
	repeatPassword: string;
}
