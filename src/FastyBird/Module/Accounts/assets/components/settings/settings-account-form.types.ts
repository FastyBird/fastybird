import { IAccount } from '../../models/accounts/types';
import { FormResultTypes, LayoutType } from '../../types';

export interface ISettingsAccountFormProps {
	account: IAccount;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultTypes;
	remoteFormReset?: boolean;
	layout?: LayoutType;
}

export interface ISettingsAccountForm {
	emailAddress: string;
	firstName: string;
	lastName: string;
	middleName?: string;
	language: string;
	weekStart: number;
	timezone: string;
	dateFormat: string;
	timeFormat: string;
}
