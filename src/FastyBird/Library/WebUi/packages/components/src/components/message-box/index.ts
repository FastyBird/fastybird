import MessageBox from './src/method';

import type { App } from 'vue';
import type { SFCWithInstall } from '@fastybird/web-ui-utils';

const _MessageBox = MessageBox as SFCWithInstall<typeof MessageBox>;

_MessageBox.install = (app: App): void => {
	_MessageBox._context = app._context;
	app.config.globalProperties.$msgbox = _MessageBox;
	app.config.globalProperties.$messageBox = _MessageBox;
	app.config.globalProperties.$alert = _MessageBox.alert;
	app.config.globalProperties.$confirm = _MessageBox.confirm;
	app.config.globalProperties.$prompt = _MessageBox.prompt;
};

export default _MessageBox;
export const FbMessageBox = _MessageBox;

export * from './src/message-box';
