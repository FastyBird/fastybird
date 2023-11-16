export enum AccountState {
	ACTIVE = 'active',
	BLOCKED = 'blocked',
	DELETED = 'deleted',
	NOT_ACTIVATED = 'not_activated',
	APPROVAL_WAITING = 'approval_waiting',
}

export enum IdentityState {
	ACTIVE = 'active',
	BLOCKED = 'blocked',
	DELETED = 'deleted',
	INVALID = 'invalid',
}

export interface AccountDocument {
	id: string;
	first_name: string;
	last_name: string;
	middle_name: string;
	state: AccountState;
	registered: string | null;
	last_visit: string | null;
	email: string;
	language: string;
	roles: string[];
}

export interface EmailDocument {
	id: string;
	address: string;
	default: boolean;
	verified: boolean;
	private: boolean;
	public: boolean;
	account: string;
}

export interface IdentityDocument {
	id: string;
	state: IdentityState;
	uid: string;
	password?: string;
	account: string;
}

export interface RoleDocument {
	id: string;
	name: string;
	description: string;
	anonymous: boolean;
	authenticated: boolean;
	administrator: boolean;
	parent: string | null;
}
