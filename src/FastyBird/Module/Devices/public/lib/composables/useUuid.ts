import { v4 as uuid, validate as uuidValidate, version as uuidVersion } from 'uuid';

export function useUuid() {
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
