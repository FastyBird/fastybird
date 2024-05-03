<template>
	<span :class="ns.e('item')">
		<span ref="link" :class="[ns.e('inner'), ns.is('link', !!props.to)]" role="link" @click="onClick">
			<slot />
		</span>

		<fb-icon v-if="breadcrumbContext?.separatorIcon" :class="ns.e('separator')">
			<component :is="breadcrumbContext.separatorIcon" />
		</fb-icon>

		<span v-else :class="ns.e('separator')" role="presentation">
			{{ breadcrumbContext?.separator }}
		</span>
	</span>
</template>

<script lang="ts" setup>
import { getCurrentInstance, inject, ref } from 'vue';

import { useNamespace } from '@fastybird/web-ui-hooks';

import { FbIcon } from '../../icon';
import { breadcrumbContextKey } from './constants';
import { breadcrumbItemProps } from './breadcrumb-item';

import type { Router } from 'vue-router';

defineOptions({
	name: 'FbBreadcrumbItem',
});

const props = defineProps(breadcrumbItemProps);

const instance = getCurrentInstance()!;
const ns = useNamespace('breadcrumb');

const breadcrumbContext = inject(breadcrumbContextKey, undefined);

const router = instance.appContext.config.globalProperties.$router as Router;

const link = ref<HTMLSpanElement | undefined>();

const onClick = (): void => {
	if (!props.to || !router) {
		return;
	}

	props.replace ? router.replace(props.to) : router.push(props.to);
};
</script>
