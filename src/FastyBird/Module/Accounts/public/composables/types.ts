import { ComputedRef } from 'vue';
import { AxiosResponse } from 'axios';

export interface UseFlashMessage {
	success: (message: string) => void;
	info: (message: string) => void;
	error: (message: string) => void;
	exception: (exception: Error, errorMessage: string) => void;
	requestError: (response: AxiosResponse, errorMessage: string) => void;
}

export interface UseBreakpoints {
	isExtraSmallDevice: ComputedRef<boolean>;
}
