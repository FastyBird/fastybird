<template>
	<footer
		v-if="!['phone', 'tablet'].includes(props.layout)"
		:class="ns.b()"
	>
		<slot>
			<div
				v-if="props.showLeftBtn"
				:class="ns.e('left-button')"
			>
				<slot name="left-button">
					<el-button
						v-if="!('left-button' in $slots)"
						type="default"
						size="large"
						text
						@click.prevent="$emit('leftClick', $event)"
					>
						{{ props.leftBtnLabel ?? t('component.dialog.leftButton') }}
					</el-button>
				</slot>
			</div>

			<div
				v-if="props.showRightBtn"
				:class="ns.e('right-button')"
			>
				<slot name="right-button">
					<el-button
						v-if="!('right-button' in $slots)"
						:type="props.type"
						size="large"
						plain
						@click.prevent="$emit('rightClick', $event)"
					>
						{{ props.rightBtnLabel ?? t('component.dialog.rightButton') }}
					</el-button>
				</slot>
			</div>
		</slot>
	</footer>
</template>

<script setup lang="ts">
import { ElButton, useLocale, useNamespace } from 'element-plus';

import { dialogFooterProps, dialogFooterEmits } from './dialog-footer';

const { t } = useLocale();
const ns = useNamespace('dialog-footer');

defineOptions({
	name: 'FbDialogFooter',
});

const props = defineProps(dialogFooterProps);

defineEmits(dialogFooterEmits);
</script>
