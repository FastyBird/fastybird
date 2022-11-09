import { v4 as uuid, validate as uuidValidate, version as uuidVersion } from 'uuid';

import { UseUuid } from '@/lib/composables/types';

export function useUuid(): UseUuid {
	const generate = (): string => {
		return uuid();
	};

	const validate = (uuid: string): boolean => {
		return uuidValidate(uuid) && uuidVersion(uuid) === 4;
	};

	return {
		generate,
		validate,
	};
}
