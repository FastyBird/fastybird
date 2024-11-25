import { FormResultType, IAccount, LayoutType } from '../../types';

export interface ISettingsAccountFormProps {
	account: IAccount;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FormResultType;
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
