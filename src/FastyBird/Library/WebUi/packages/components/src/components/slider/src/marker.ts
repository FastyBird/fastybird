import { computed, defineComponent, h } from 'vue';

import { buildProps, definePropType, isString } from '@fastybird/web-ui-utils';
import { useNamespace } from '@fastybird/web-ui-hooks';

import type { CSSProperties, ExtractPropTypes } from 'vue';

export const sliderMarkerProps = buildProps({
	mark: {
		type: definePropType<string | { style: CSSProperties; label: any } | undefined>([String, Object]),
		default: undefined,
	},
} as const);

export type SliderMarkerProps = ExtractPropTypes<typeof sliderMarkerProps>;

export default defineComponent({
	name: 'FbSliderMarker',

	props: sliderMarkerProps,

	setup(props) {
		const ns = useNamespace('slider');
		const label = computed(() => {
			return isString(props.mark) ? props.mark : props.mark!.label;
		});
		const style = computed(() => (isString(props.mark) ? undefined : props.mark!.style));

		return () =>
			h(
				'div',
				{
					class: ns.e('marks-text'),
					style: style.value,
				},
				label.value
			);
	},
});
