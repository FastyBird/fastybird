<template>
	<el-icon :size="props.size">
		<fas-plug-circle-bolt />
	</el-icon>
</template>

<script setup lang="ts">
import { ElIcon } from 'element-plus';

import { FasPlugCircleBolt } from '@fastybird/web-ui-icons';

import { IPluginsPluginDefaultIconProps } from './plugins-plugin-default-icon.types';

defineOptions({
	name: 'PluginsPluginDefaultIcon',
});

const props = withDefaults(defineProps<IPluginsPluginDefaultIconProps>(), {
	size: 100,
});
</script>
