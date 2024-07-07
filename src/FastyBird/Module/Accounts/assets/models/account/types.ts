import { _GettersTree } from 'pinia';

import { IAccount } from '../accounts/types';
import { IEmail, IEmailMeta } from '../emails/types';
import { IIdentity } from '../identities/types';

// STORE
// =====

export interface IAccountState {
	semaphore: IAccountStateSemaphore;
	loaded: boolean;
}

export interface IAccountGetters extends _GettersTree<IAccountState> {
	emails: () => () => IEmail[];
}

export interface IAccountActions {
	edit: (payload: IAccountEditActionPayload) => Promise<boolean>;
	addEmail: (payload: IAccountAddEmailActionPayload) => Promise<IEmail>;
	editEmail: (payload: IAccountEditEmailActionPayload) => Promise<IEmail>;
	editIdentity: (payload: IAccountEditIdentityActionPayload) => Promise<boolean>;
	requestReset: (payload: IAccountRequestResetActionPayload) => Promise<boolean>;
	register: (payload: IAccountRegisterActionPayload) => Promise<boolean>;
}

// STORE STATE
// ===========

interface IAccountStateSemaphore {
	updating: boolean;
	creating: boolean;
}

// STORE ACTIONS
// =============

export interface IAccountEditActionPayload {
	data: {
		details: {
			firstName: IAccount['details']['firstName'];
			lastName: IAccount['details']['lastName'];
			middleName?: IAccount['details']['middleName'];
		};

		language?: IAccount['language'];

		weekStart?: IAccount['weekStart'];
		dateTime?: {
			timezone?: IAccount['dateTime']['timezone'];
			dateFormat?: IAccount['dateTime']['dateFormat'];
			timeFormat?: IAccount['dateTime']['timeFormat'];
		};
	};
}

export interface IAccountAddEmailActionPayload {
	id?: IEmail['id'];
	type: IEmailMeta;

	draft?: IEmail['draft'];

	data: {
		address: IEmail['address'];
		default?: IEmail['default'];
		private?: IEmail['private'];
	};
}

export interface IAccountEditEmailActionPayload {
	id: IEmail['id'];

	data: {
		default?: IEmail['default'];
		private?: IEmail['private'];
	};
}

export interface IAccountEditIdentityActionPayload {
	id: IIdentity['id'];

	data: {
		password: {
			current: IIdentity['password'];
			new: IIdentity['password'];
		};
	};
}

export interface IAccountRegisterActionPayload {
	emailAddress: IEmail['address'];
	firstName: IAccount['details']['firstName'];
	lastName: IAccount['details']['lastName'];
	password: IIdentity['password'];
}

export interface IAccountRequestResetActionPayload {
	uid: IIdentity['uid'];
}
