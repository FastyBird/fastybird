import { withInstall } from '@fastybird/web-ui-utils';

import Form from './src/form.vue';
import FormItem from '../form-item/src/form-item.vue';

export const FbForm = withInstall(Form, {
	FormItem,
});

export default FbForm;

export * from './src/form';
export * from './src/types';
export * from './src/hooks';

export type FormInstance = InstanceType<typeof Form>;
