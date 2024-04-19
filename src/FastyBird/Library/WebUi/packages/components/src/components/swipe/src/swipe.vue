<template>
	<div
		ref="container"
		:data-disabled="props.disabled"
		:class="ns.b()"
	>
		<template
			v-for="(item, index) in props.items"
			:key="index"
		>
			<fb-swipe-item
				ref="elements"
				v-model:revealed="innerRevealed[index]"
				:threshold="props.threshold"
				:disabled="props.itemDisabled(item) || props.disabled"
				:class="ns.e('item')"
				@closed="handleClosed(item, index)"
				@revealed="handleRevealed(item, index, $event)"
				@left-revealed="$emit('leftRevealed', { index, item, close: $event.close })"
				@right-revealed="$emit('rightRevealed', { index, item, close: $event.close })"
				@active="$emit('active', $event)"
			>
				<template #content="{ revealed: rowRevealed, disabled: rowDisabled, revealLeft, revealRight, close }">
					<slot
						:item="item"
						:index="index"
						:revealed="rowRevealed"
						:disabled="rowDisabled"
						:reveal-left="revealLeft"
						:reveal-right="revealRight"
						:close="close"
					/>
				</template>

				<template
					v-if="'left' in $slots"
					#left="{ close }"
				>
					<slot
						:item="item"
						:index="index"
						:close="close"
						name="left"
					/>
				</template>

				<template
					v-if="'right' in $slots"
					#right="{ close }"
				>
					<slot
						:item="item"
						:index="index"
						:close="close"
						name="right"
					/>
				</template>
			</fb-swipe-item>
		</template>
	</div>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue';
import isEmpty from 'lodash/isEmpty';

import { useNamespace } from '@fastybird/web-ui-hooks';

import { FbSwipeItemInstance } from './instance';
import FbSwipeItem from './item.vue';
import { swipeEmits, swipeProps } from './swipe';

import type { SwipeActionsOutDir } from './swipe';

defineOptions({
	name: 'FbSwipe',
});

const props = defineProps(swipeProps);
const emit = defineEmits(swipeEmits);

const ns = useNamespace('swipe');

const container = ref<HTMLElement | null>(null);
const elements = ref<FbSwipeItemInstance[]>([]);
const innerRevealed = ref<{ [key: number]: SwipeActionsOutDir }>(props.revealed || {});

const revealLeft = (index: number): void => {
	if (!(index in elements.value)) {
		return;
	}

	elements.value[index].revealLeft();
};

const revealRight = (index: number): void => {
	if (!(index in elements.value)) {
		return;
	}

	elements.value[index].revealRight();
};

const close = (index?: number): void => {
	if (isEmpty(elements.value)) {
		return;
	}

	if (index === undefined) {
		return Object.values(elements.value).forEach((element) => element.close());
	}

	if (!(index in elements.value)) {
		return;
	}

	elements.value[index].close();
};

const isRevealed = (index: number): boolean => {
	return index in innerRevealed.value || false;
};

const handleRevealed = (item: any, index: number, event: { side: SwipeActionsOutDir; close: () => void }): void => {
	emit('revealed', {
		index,
		item,
		side: event.side,
		close: event.close,
	});

	emitRevealed({
		...innerRevealed.value,
		[index]: event.side,
	});
};

const handleClosed = (item: any, index: number): void => {
	emit('closed', {
		index,
		item,
	});

	const { [index]: omit, ...newRevealed } = innerRevealed.value;

	emitRevealed(newRevealed);
};

const emitRevealed = (val: { [key: number]: SwipeActionsOutDir }): void => {
	emit('update:revealed', val);
};

watch(
	() => props.revealed,
	(val): void => {
		innerRevealed.value = val as { [key: number]: SwipeActionsOutDir };
	}
);

watch(
	() => props.items,
	(): void => {
		emitRevealed({});
	}
);

defineExpose({
	/** @description */
	revealLeft,
	/** @description */
	revealRight,
	/** @description */
	isRevealed,
	/** @description */
	close,
});
</script>
