<template>
	<slot>
		<header
			ref="headerRef"
			:class="[ns.b(), ns.m('variant-' + props.variant), { 'show-close': props.closable }]"
		>
			<div :class="[ns.e('inner')]">
				<div
					v-if="!('description' in $slots)"
					:class="[ns.e('heading')]"
				>
					<div
						v-if="'icon' in $slots || props.icon"
						:class="[ns.e('icon')]"
					>
						<fb-icon>
							<slot name="icon">
								<component
									:is="props.icon"
									v-if="props.icon"
								/>
							</slot>
						</fb-icon>
					</div>

					<div
						v-if="'title' in $slots || props.title"
						:class="[ns.e('title'), { [ns.m('with-subtitle')]: 'subtitle' in $slots || props.subtitle }]"
						:aria-level="props.ariaLevel"
						role="heading"
					>
						<slot name="title">{{ props.title }}</slot>

						<small
							v-if="'subtitle' in $slots || props.subtitle"
							:class="[ns.e('subtitle')]"
						>
							<slot name="subtitle">{{ props.subtitle }}</slot>
						</small>
					</div>
				</div>

				<button
					v-if="props.closable"
					:aria-label="t('component.drawer.close')"
					:class="[ns.e('close')]"
					type="button"
					@click.prevent="emit('close', $event)"
				>
					<fb-icon>
						<component :is="props.closeIcon || close" />
					</fb-icon>
				</button>
			</div>

			<div
				v-if="'description' in $slots"
				:class="[ns.e('description')]"
			>
				<div
					v-if="'icon' in $slots || props.icon"
					:class="[ns.e('icon')]"
				>
					<slot name="icon">
						<fb-icon
							v-if="props.icon"
							:class="[ns.e('icon')]"
						>
							<component :is="props.icon" />
						</fb-icon>
					</slot>
				</div>

				<div
					v-if="'title' in $slots || props.title"
					:class="[ns.e('title'), { [ns.m('with-subtitle')]: 'subtitle' in $slots || props.subtitle }]"
					:aria-level="props.ariaLevel"
					role="heading"
				>
					<slot name="title">{{ props.title }}</slot>

					<small
						v-if="'subtitle' in $slots || props.subtitle"
						:class="[ns.e('subtitle')]"
					>
						<slot name="subtitle">{{ props.subtitle }}</slot>
					</small>
				</div>

				<div :class="[ns.e('content')]">
					<slot name="description" />
				</div>
			</div>
		</header>
	</slot>
</template>

<script setup lang="ts">
import { inject } from 'vue';

import { useLocale, useNamespace } from '@fastybird/web-ui-hooks';
import { CloseComponentsMap } from '@fastybird/web-ui-utils';

import { FbIcon } from '../../icon';
import { drawerContextKey } from './constants';
import { drawerHeaderProps, drawerHeaderEmits } from './drawer-header';

defineOptions({
	name: 'FbDrawerHeader',
});

const props = defineProps(drawerHeaderProps);
const emit = defineEmits(drawerHeaderEmits);

const { t } = useLocale();
const { close } = CloseComponentsMap;

const ns = useNamespace('drawer-header');

const { headerRef } = inject(drawerContextKey)!;
</script>
