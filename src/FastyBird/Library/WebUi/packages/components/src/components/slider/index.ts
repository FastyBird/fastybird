import { withInstall } from '@fastybird/web-ui-utils';

import Slider from './src/slider.vue';

export const FbSlider = withInstall(Slider);
export default FbSlider;

export * from './src/slider';
export * from './src/constants';

export type { FbSliderInstance, FbSliderButtonInstance } from './src/instance';
