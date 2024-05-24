<template>
	<div :class="[ns.b(), ns.m('size-' + props.size)]">
		<div :class="ns.e('icon')">
			<el-icon>
				<circle-close-filled />
			</el-icon>
		</div>

		<p :class="ns.e('content')">
			{{ props.text }}
		</p>
	</div>
</template>

<script setup lang="ts">
import { ElIcon, useNamespace } from 'element-plus';

import { CircleCloseFilled } from '@element-plus/icons-vue';

import { componentLoadingErrorProps } from './component-loading-error';

defineOptions({
	name: 'FbComponentLoadingError',
});

const props = defineProps(componentLoadingErrorProps);

const ns = useNamespace('component-loading-error');
</script>
