// STORE STATE
// ===========

export interface IAccountState {
	semaphore: IAccountStateSemaphore;
	loaded: boolean;
}

interface IAccountStateSemaphore {
	updating: boolean;
	creating: boolean;
}

// STORE ACTIONS
// =============

export interface IAccountEditActionPayload {
	data: {
		details: {
			firstName: string;
			lastName: string;
			middleName?: string | null;
		};

		language?: string;

		weekStart?: number;
		dateTime: {
			timezone?: string;
			dateFormat?: string;
			timeFormat?: string;
		};
	};
}

export interface IAccountAddEmailActionPayload {
	id?: string;
	type?: string;

	draft?: boolean;

	data: {
		address: string;
		default?: boolean;
		private?: boolean;
	};
}

export interface IAccountEditEmailActionPayload {
	id: string;

	data: {
		default?: boolean;
		private?: boolean;
	};
}

export interface IAccountEditIdentityActionPayload {
	id: string;

	data: {
		password: {
			current: string;
			new: string;
		};
	};
}

export interface IAccountRegisterActionPayload {
	emailAddress: string;
	firstName: string;
	lastName: string;
	password: string;
}

export interface IAccountRequestResetActionPayload {
	uid: string;
}
