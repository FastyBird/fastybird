import { ref, Ref, watch } from 'vue';
import capitalize from 'lodash.capitalize';

export function useEntityTitle(entity: any): Ref<string> {
	const title = ref<string>(entity.name ?? capitalize(entity.identifier));

	watch(
		(): any => entity,
		(val): void => {
			title.value = val.name ?? capitalize(val.identifier);
		}
	);

	return title;
}
