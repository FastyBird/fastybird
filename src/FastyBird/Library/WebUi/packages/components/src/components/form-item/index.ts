import { withNoopInstall } from '@fastybird/web-ui-utils';

import FormItem from './src/form-item.vue';

export const FbFormItem = withNoopInstall(FormItem);

export default FbFormItem;

export * from './src/form-item';
export * from './src/types';
export * from './src/hooks';
export * from './src/constants';

export type FormItemInstance = InstanceType<typeof FormItem>;
