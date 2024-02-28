import * as Sentry from '@sentry/vue';

import { useSession } from '../../models';

const accountGuard = (): boolean | { name: string } | undefined => {
	const sessionStore = useSession();

	const account = sessionStore.account;

	if (import.meta.env.PROD && account !== null) {
		Sentry.setUser({
			email: account.email?.address,
		});
	}

	return;
};

export default accountGuard;
