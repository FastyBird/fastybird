<template>
	<component
		v-bind="attributes"
		:is="props.tag"
		ref="element"
		:class="[
			ns.b(),
			ns.m('variant-' + props.variant),
			ns.m('size-' + props.size),
			ns.is('disabled', disabled),
			ns.is('loading', props.loading || remoteLoading),
			ns.is('plain', props.plain),
			ns.is('round', props.round),
			ns.is('circle', props.circle),
			ns.is('link', props.link),
			ns.is('text', props.text),
			ns.is('block', props.block),
			ns.is('uppercase', props.uppercase),
			ns.is('active', props.active),
			ns.is('block', props.block),
			ns.is('has-bg', props.bg),
		]"
		:style="buttonStyle"
		role="button"
		@click="onClick($event)"
	>
		<template v-if="props.loading || remoteLoading">
			<slot name="loading">
				<fb-icon v-if="props.loadingIcon" :class="[ns.is('loading'), 'is-spin']">
					<component :is="props.loadingIcon" />
				</fb-icon>
				<fb-spinner v-else :variant="props.variant" :class="ns.is('loading')" />
			</slot>
		</template>

		<fb-icon v-else-if="props.icon || 'icon' in $slots">
			<slot name="icon">
				<component :is="props.icon" v-if="props.icon" />
			</slot>
		</fb-icon>

		<template v-if="remoteText">
			<span>{{ remoteText }}</span>
		</template>
		<span v-else-if="'default' in $slots">
			<slot />
		</span>
	</component>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';

import { useFormDisabled } from '../../form';
import { useFormItem } from '../../form-item';
import { FbIcon } from '../../icon';
import { FbSpinner } from '../../spinner';
import { buttonEmits, buttonProps, ButtonButtonTypes } from './button';
import { useButtonCustomStyle } from './hooks';

defineOptions({
	name: 'FbButton',
});

const props = defineProps(buttonProps);
const emit = defineEmits(buttonEmits);

const ns = useNamespace('button');

const { form } = useFormItem();
const _disabled = useFormDisabled();
const buttonStyle = useButtonCustomStyle(props);

const element = ref<HTMLElement | null>(null);

const remoteLoading = ref<boolean | undefined>(undefined);
const remoteText = ref<string | undefined>(undefined);

const attributes = computed<{ ariaDisabled?: boolean; disabled?: boolean; autofocus?: boolean; type?: string }>(
	(): { ariaDisabled?: boolean; disabled?: boolean; autofocus?: boolean; type?: string } => {
		if ([ButtonButtonTypes.BUTTON, ButtonButtonTypes.SUBMIT, ButtonButtonTypes.RESET].includes(props.type)) {
			return {
				ariaDisabled: _disabled.value || props.loading || remoteLoading.value || props.disabled,
				disabled: _disabled.value || props.loading || remoteLoading.value || props.disabled,
				autofocus: props.autofocus,
				type: props.type,
			};
		}

		return {};
	}
);

const onClick = (evt: MouseEvent): void => {
	if (props.type === ButtonButtonTypes.RESET) {
		form?.resetFields();
	}

	emit('click', evt);
};

defineExpose({
	/** @description button size */
	size: props.size,
	/** @description button variant */
	variant: props.variant,
	/** @description button disabled */
	disabled: props.disabled,
	/** @description button loading status */
	loading: remoteLoading,
	/** @description button text */
	text: remoteText,
});
</script>
