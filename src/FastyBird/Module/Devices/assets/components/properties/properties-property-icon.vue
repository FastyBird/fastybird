<template>
	<el-icon :size="props.size">
		<fas-lightbulb />
	</el-icon>
</template>

<script setup lang="ts">
import { ElIcon } from 'element-plus';

import { FasLightbulb } from '@fastybird/web-ui-icons';

import { IPropertiesPropertyIconProps } from './properties-property-icon.types';

defineOptions({
	name: 'PropertiesPropertyIcon',
});

const props = defineProps<IPropertiesPropertyIconProps>();
</script>
