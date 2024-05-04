import { onMounted, ref } from "vue";

import { Meta, StoryObj } from "@storybook/vue3";
import { FbScrollbar, FbButton, FbSlider } from "@fastybird/web-ui-components";
import { EffectTypes } from "@fastybird/web-ui-constants";

import "./fb-scrollbar.stories.scss";

const meta: Meta<typeof FbScrollbar> = {
    component: FbScrollbar,
    title: "Components/Basic/Scrollbar",
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbScrollbar>;

export const BasicUsage: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-scrollbar height="400px">
		<p v-for="item in 20" :key="item" class="scrollbar-demo-item">{{ item }}</p>
	</fb-scrollbar>
</template>

<style scoped>
.scrollbar-demo-item {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 50px;
  margin: 10px;
  text-align: center;
  border-radius: 4px;
  background: var(--fb-color-primary-light-9);
  color: var(--fb-color-primary);
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbScrollbar },
        template: `
<div class="fb-scrollbar-story-block">
	<fb-scrollbar height="400px">
		<p v-for="item in 20" :key="item" class="fb-scrollbar-story-block__item">
			{{ item }}
		</p>
	</fb-scrollbar>
</div>`,
    }),
};

export const WithShadow: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-scrollbar height="400px" shadow="${EffectTypes.DARK}">
		<p v-for="item in 20" :key="item" class="scrollbar-demo-item">{{ item }}</p>
	</fb-scrollbar>
</template>

<style scoped>
.scrollbar-demo-item {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 50px;
  margin: 10px;
  text-align: center;
  border-radius: 4px;
  background: var(--fb-color-primary-light-9);
  color: var(--fb-color-primary);
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbScrollbar },
        template: `
<div class="fb-scrollbar-story-block">
	<fb-scrollbar height="400px" shadow="${EffectTypes.DARK}">
		<p v-for="item in 20" :key="item" class="fb-scrollbar-story-block__item">
			{{ item }}
		</p>
	</fb-scrollbar>
</div>`,
    }),
};

export const Horizontal: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-scrollbar>
		<div class="scrollbar-flex-content">
			<p v-for="item in 50" :key="item" class="scrollbar-demo-item">
				{{ item }}
			</p>
		</div>
	</fb-scrollbar>
</template>

<style scoped>
.scrollbar-flex-content {
  display: flex;
}

.scrollbar-demo-item {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100px;
  height: 50px;
  margin: 10px;
  text-align: center;
  border-radius: 4px;
  background: var(--fb-color-danger-light-9);
  color: var(--fb-color-danger);
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbScrollbar },
        template: `
<div class="fb-scrollbar-story-block">
	<fb-scrollbar>
		<div class="fb-scrollbar-story-block__inner">
			<p v-for="item in 50" :key="item" class="fb-scrollbar-story-block__item--vertical">
				{{ item }}
			</p>
		</div>
	</fb-scrollbar>
</div>`,
    }),
};

export const MaxHeight: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button @click="add">Add Item</fb-button>
	<fb-button @click="onDelete">Delete Item</fb-button>
	<fb-scrollbar max-height="400px">
		<p v-for="item in count" :key="item" class="scrollbar-demo-item">
			{{ item }}
		</p>
	</fb-scrollbar>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const count = ref(3);

const add = () => {
  count.value++;
}

const onDelete = () => {
  if (count.value > 0) {
    count.value--;
  }
}
</script>

<style scoped>
.scrollbar-demo-item {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 50px;
  margin: 10px;
  text-align: center;
  border-radius: 4px;
  background: var(--fb-color-primary-light-9);
  color: var(--fb-color-primary);
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbScrollbar, FbButton },
        setup: () => {
            const count = ref(3);

            const add = () => {
                count.value++;
            };

            const onDelete = () => {
                if (count.value > 0) {
                    count.value--;
                }
            };

            return {
                count,
                add,
                onDelete,
            };
        },
        template: `
<div class="fb-scrollbar-story-block">
	<fb-button @click="add">Add Item</fb-button>

	<fb-button @click="onDelete">Delete Item</fb-button>

	<fb-scrollbar max-height="400px">
		<p v-for="item in count" :key="item" class="fb-scrollbar-story-block__item">
			{{ item }}
		</p>
	</fb-scrollbar>
</div>`,
    }),
};

export const Manual: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-scrollbar ref="scrollbarRef" height="400px" always @scroll="scroll">
		<div ref="innerRef">
			<p v-for="item in 20" :key="item" class="scrollbar-demo-item">
				{{ item }}
			</p>
		</div>
	</fb-scrollbar>

	<fb-slider
		v-model="value"
		:max="max"
		:format-tooltip="formatTooltip"
		@input="inputSlider"
	/>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { FbScrollbar } from 'element-plus'

const max = ref(0);
const value = ref(0);
const innerRef = ref<HTMLDivElement | undefined>();
const scrollbarRef = ref<InstanceType<typeof FbScrollbar>>();

onMounted((): void => {
  max.value = innerRef.value!.clientHeight - 380;
})

const inputSlider = (value: number): void => {
  scrollbarRef.value!.setScrollTop(value);
}

const scroll = ({ scrollTop }): void => {
  value.value = scrollTop;
}

const formatTooltip = (value: number): string => {
  return \`\$\{value\} px\`;
}
</script>

<style scoped>
.scrollbar-demo-item {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 50px;
  margin: 10px;
  text-align: center;
  border-radius: 4px;
  background: var(--fb-color-primary-light-9);
  color: var(--fb-color-primary);
}

.fb-slider {
  margin-top: 20px;
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbScrollbar, FbSlider },
        setup: () => {
            const max = ref<number>(0);
            const value = ref<number>(0);
            const innerRef = ref<HTMLDivElement | undefined>();
            const scrollbarRef = ref<InstanceType<typeof FbScrollbar>>();

            onMounted((): void => {
                max.value = innerRef.value!.clientHeight - 380;
            });

            const inputSlider = (value: number): void => {
                scrollbarRef.value!.setScrollTop(value);
            };
            const scroll = ({ scrollTop }): void => {
                value.value = scrollTop;
            };
            const formatTooltip = (value: number): string => {
                return `${value} px`;
            };

            return {
                max,
                value,
                innerRef,
                scrollbarRef,
                inputSlider,
                scroll,
                formatTooltip,
            };
        },
        template: `
<div class="fb-scrollbar-story-block">
	<fb-scrollbar ref="scrollbarRef" height="400px" always @scroll="scroll">
		<div ref="innerRef">
			<p v-for="item in 20" :key="item" class="fb-scrollbar-story-block__item">
				{{ item }}
			</p>
		</div>
	</fb-scrollbar>

	<fb-slider
		v-model="value"
		:max="max"
		:format-tooltip="formatTooltip"
		@input="inputSlider"
	/>
</div>`,
    }),
};
