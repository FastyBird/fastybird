import { FbFormResultTypes } from '@fastybird/web-ui-library';

import { IAccount } from '@/types';

export interface ISettingsAccountProps {
	account: IAccount;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FbFormResultTypes;
	remoteFormReset?: boolean;
}
