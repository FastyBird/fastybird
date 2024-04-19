import { withInstall } from '@fastybird/web-ui-utils';

import AppBar from './src/app-bar.vue';
import AppBarButton from './src/button.vue';
import AppBarContent from './src/content.vue';
import AppBarHeading from './src/heding.vue';
import AppBarIcon from './src/icon.vue';

export const FbAppBar = withInstall(AppBar);
export const FbAppBarButton = withInstall(AppBarButton);
export const FbAppBarContent = withInstall(AppBarContent);
export const FbAppBarHeading = withInstall(AppBarHeading);
export const FbAppBarIcon = withInstall(AppBarIcon);

export default FbAppBar;

export * from './src/app-bar';
export * from './src/button';
export * from './src/content';
export * from './src/heading';
export * from './src/icon';

export type {
	FbAppBarInstance,
	FbAppBarButtonInstance,
	FbAppBarContentInstance,
	FbAppBarHeadingInstance,
	FbAppBarIconInstance,
} from './src/instance';
