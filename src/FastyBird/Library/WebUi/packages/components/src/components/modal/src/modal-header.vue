<template>
	<slot>
		<header
			ref="headerRef"
			:class="[ns.b(), ns.m('variant-' + props.variant), ns.m('layout-' + props.layout), { 'show-close': props.showClose }]"
		>
			<div :class="[ns.e('inner')]">
				<template v-if="props.layout === LayoutTypes.PHONE || layout === LayoutTypes.TABLET">
					<div :class="[ns.e('heading')]">
						<div
							v-if="('title' in $slots || props.title) && !('description' in $slots)"
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

					<div :class="[ns.e('left-button')]">
						<slot
							v-if="props.showLeftBtn"
							name="left-button"
						>
							<fb-button
								:variant="VariantTypes.DEFAULT"
								:size="ComponentSizeTypes.DEFAULT"
								text
								uppercase
								@click.prevent="$emit('leftClick', $event)"
							>
								{{ props.leftBtnLabel ?? t('component.modal.leftButton') }}
							</fb-button>
						</slot>
					</div>

					<div :class="[ns.e('right-button')]">
						<slot
							v-if="props.showRightBtn"
							name="right-button"
						>
							<fb-button
								:variant="VariantTypes.DEFAULT"
								:size="ComponentSizeTypes.DEFAULT"
								text
								uppercase
								@click.prevent="$emit('rightClick', $event)"
							>
								{{ props.rightBtnLabel ?? t('component.modal.rightButton') }}
							</fb-button>
						</slot>
					</div>
				</template>

				<template v-else>
					<div
						v-if="!('description' in $slots)"
						:class="[ns.e('heading')]"
					>
						<fb-icon
							v-if="'icon' in $slots || props.icon"
							:class="[ns.e('icon')]"
						>
							<slot name="icon">
								<component
									:is="props.icon"
									v-if="props.icon"
								/>
							</slot>
						</fb-icon>

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
						v-if="props.showClose && props.closable"
						:aria-label="t('component.modal.close')"
						:class="[ns.e('close')]"
						type="button"
						@click.prevent="$emit('close', $event)"
					>
						<fb-icon>
							<component :is="props.closeIcon || close" />
						</fb-icon>
					</button>
				</template>
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
import { ComponentSizeTypes, LayoutTypes, VariantTypes } from '@fastybird/web-ui-constants';

import { FbButton } from '../../button';
import { FbIcon } from '../../icon';
import { modalContextKey } from './constants';
import { modalHeaderProps, modalHeaderEmits } from './modal-header';

const { t } = useLocale();
const ns = useNamespace('modal-header');
const { close } = CloseComponentsMap;

defineOptions({
	name: 'FbModalHeader',
});

const props = defineProps(modalHeaderProps);

defineEmits(modalHeaderEmits);

const { headerRef } = inject(modalContextKey)!;
</script>
