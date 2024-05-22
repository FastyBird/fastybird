<template>
	<slot>
		<div
			ref="headerRef"
			:class="[ns.b(), ns.m('type-' + props.type), ns.m('layout-' + props.layout), { 'show-close': props.showClose }]"
		>
			<div :class="[ns.e('inner')]">
				<template v-if="props.layout === 'phone' || props.layout === 'tablet'">
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
							<el-button
								type="default"
								size="default"
								text
								@click.prevent="$emit('leftClick', $event)"
							>
								{{ props.leftBtnLabel ?? t('component.dialog.leftButton') }}
							</el-button>
						</slot>
					</div>

					<div :class="[ns.e('right-button')]">
						<slot
							v-if="props.showRightBtn"
							name="right-button"
						>
							<el-button
								type="default"
								size="default"
								text
								@click.prevent="$emit('rightClick', $event)"
							>
								{{ props.rightBtnLabel ?? t('component.dialog.rightButton') }}
							</el-button>
						</slot>
					</div>
				</template>

				<template v-else>
					<div
						v-if="!('description' in $slots)"
						:class="[ns.e('heading')]"
					>
						<el-icon
							v-if="'icon' in $slots || props.icon"
							:class="[ns.e('icon')]"
						>
							<slot name="icon">
								<component
									:is="props.icon"
									v-if="props.icon"
								/>
							</slot>
						</el-icon>

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
						v-if="props.showClose"
						:aria-label="t('component.dialog.close')"
						:class="[ns.e('close')]"
						type="button"
						@click.prevent="$emit('close', $event)"
					>
						<el-icon>
							<component :is="props.closeIcon || Close" />
						</el-icon>
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
						<el-icon
							v-if="props.icon"
							:class="[ns.e('icon')]"
						>
							<component :is="props.icon" />
						</el-icon>
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
		</div>
	</slot>
</template>

<script setup lang="ts">
import { inject } from 'vue';
import { ElButton, ElIcon, dialogInjectionKey, useLocale, useNamespace } from 'element-plus';

import { Close } from '@element-plus/icons-vue';

import { dialogHeaderProps, dialogHeaderEmits } from './dialog-header';

const { t } = useLocale();
const ns = useNamespace('dialog-header');

defineOptions({
	name: 'FbDialogHeader',
});

const props = defineProps(dialogHeaderProps);

defineEmits(dialogHeaderEmits);

const { headerRef } = inject(dialogInjectionKey)!;
</script>
