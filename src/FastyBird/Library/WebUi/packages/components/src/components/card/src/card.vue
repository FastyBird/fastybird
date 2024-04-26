<template>
	<div
		:class="[
			ns.b(),
			ns.m('variant-' + props.variant),
			ns.is(`${props.shadow}-shadow`),
			ns.is('bordered', props.bordered),
			ns.is(props.effect ?? EffectTypes.LIGHT),
			ns.is('loading', props.loading),
		]"
	>
		<div
			v-if="'header' in $slots || props.header"
			:class="[
				ns.e('header-wrapper'),
				ns.em('header-wrapper', 'variant-' + props.headerVariant),
				{ [ns.em('header-wrapper', 'with-footer')]: 'footer' in $slots || props.footer },
				{ [ns.em('header-wrapper', 'with-body')]: 'default' in $slots },
				props.headerClass,
			]"
			:style="props.headerStyle"
		>
			<div :class="[ns.e('header-title')]">
				<slot name="header">{{ props.header }}</slot>
			</div>

			<div v-if="'extra' in $slots || props.extra" :class="[ns.e('extra')]">
				<slot name="extra">{{ props.extra }}</slot>
			</div>
		</div>

		<div v-if="'cover' in $slots || props.cover" :class="[ns.e('cover')]">
			<slot name="cover">{{ props.cover }}</slot>
		</div>

		<div
			v-if="'default' in $slots"
			:class="[
				ns.e('body'),
				ns.em('body', 'variant-' + props.bodyVariant),
				{ [ns.em('body', 'with-header')]: 'header' in $slots || props.header },
				{ [ns.em('body', 'with-footer')]: 'footer' in $slots || props.footer },
				props.bodyClass,
			]"
			:style="props.bodyStyle"
		>
			<slot />
		</div>

		<div
			v-if="'footer' in $slots || props.footer"
			:class="[
				ns.e('footer'),
				ns.em('footer', 'variant-' + props.footerVariant),
				{ [ns.em('footer', 'with-header')]: 'header' in $slots || props.header },
				{ [ns.em('footer', 'with-body')]: 'default' in $slots },
				props.footerClass,
			]"
			:style="props.footerStyle"
		>
			<slot name="footer">{{ props.footer }}</slot>
		</div>
	</div>
</template>

<script lang="ts" setup>
import { EffectTypes } from '@fastybird/web-ui-constants';
import { useNamespace } from '@fastybird/web-ui-hooks';

import { cardProps } from './card';

defineOptions({
	name: 'FbCard',
});

const props = defineProps(cardProps);

const ns = useNamespace('card');
</script>
