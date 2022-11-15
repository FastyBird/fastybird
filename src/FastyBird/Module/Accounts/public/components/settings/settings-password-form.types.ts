import { FbFormResultTypes } from '@fastybird/web-ui-library';
import { IIdentity } from '@/models/identities/types';

export interface ISettingsPasswordProps {
	identity: IIdentity;
	remoteFormSubmit?: boolean;
	remoteFormResult?: FbFormResultTypes;
	remoteFormReset?: boolean;
}
