import { ComputedRef } from 'vue';
import { AxiosResponse } from 'axios';

import { ConnectionState } from '@fastybird/metadata-library';

export interface UseUuid {
	generate: () => string;
	validate: (uuid: string) => boolean;
}

export interface UseFlashMessage {
	success: (message: string) => void;
	info: (message: string) => void;
	error: (message: string) => void;
	exception: (exception: Error, errorMessage: string) => void;
	requestError: (response: AxiosResponse, errorMessage: string) => void;
}

export interface UseDeviceState {
	state: ComputedRef<ConnectionState>;
	isReady: ComputedRef<boolean>;
}

export interface UseConnectorState {
	state: ComputedRef<ConnectionState>;
	isReady: ComputedRef<boolean>;
}

export interface UseBreakpoints {
	isExtraSmallDevice: ComputedRef<boolean>;
	isSmallDevice: ComputedRef<boolean>;
}
