import { RouteLocationNormalized } from 'vue-router';
import get from 'lodash.get';

import { useSession } from '../../models';

const anonymousGuard = (to: RouteLocationNormalized): boolean | { name: string } | undefined => {
	const sessionStore = useSession();
	const toGuards = get(to.meta, 'guards', []);

	if (sessionStore.isSignedIn && Array.isArray(toGuards) && toGuards.includes('anonymous')) {
		return { name: 'root' };
	}
};

export default anonymousGuard;
