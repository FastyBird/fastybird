import { computed } from 'vue';
import capitalize from 'lodash/capitalize';

export function useEntityTitle(entity: any) {
	return computed<string>((): string => {
		return entity?.name ?? capitalize(entity?.identifier);
	});
}
