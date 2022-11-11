import { useToast } from 'vue-toastification';
import { AxiosResponse } from 'axios';
import get from 'lodash/get';

import { UseFlashMessage } from '@/composables/types';

export function useFlashMessage(): UseFlashMessage {
	const toast = useToast();

	const success = (message: string): void => {
		toast.success(message);
	};

	const info = (message: string): void => {
		toast.info(message);
	};

	const error = (message: string): void => {
		toast.error(message);
	};

	const exception = (exception: Error, errorMessage: string): void => {
		let errorShown = false;

		get(exception, 'response.data.errors', []).forEach((error: any): void => {
			if ('code' in error && parseInt(error.code, 10) === 422) {
				toast.error(get(error, 'detail', ''));

				errorShown = true;
			}
		});

		if (!errorShown && errorMessage !== null) {
			toast.error(errorMessage);
		}
	};

	const requestError = (response: AxiosResponse, errorMessage: string): void => {
		let errorShown = false;

		if (response && Object.prototype.hasOwnProperty.call(response, 'data') && Object.prototype.hasOwnProperty.call(response.data, 'errors')) {
			for (const key in response.data.errors) {
				if (Object.prototype.hasOwnProperty.call(response.data.errors, key) && parseInt(response.data.errors[key].code, 10) === 422) {
					toast.error(response.data.errors[key].detail);

					errorShown = true;
				}
			}
		}

		if (!errorShown && errorMessage !== null) {
			toast.error(errorMessage);
		}
	};

	return {
		success,
		info,
		error,
		exception,
		requestError,
	};
}
