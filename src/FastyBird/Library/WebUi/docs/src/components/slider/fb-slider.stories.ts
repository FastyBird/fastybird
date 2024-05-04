import { reactive, ref } from "vue";

import { Meta, StoryObj } from "@storybook/vue3";
import { FbSlider, FbRow, FbCol } from "@fastybird/web-ui-components";
import { ComponentSizeTypes, VariantTypes } from "@fastybird/web-ui-constants";

import "./fb-slider.stories.scss";

import type { CSSProperties } from "vue";

const meta: Meta<typeof FbSlider> = {
    component: FbSlider,
    title: "Components/Form/Slider",
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbSlider>;

export const BasicUsage: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Default value
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value1" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Customized initial value
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value2" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Hide Tooltip
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value3" :show-tooltip="false" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Format Tooltip
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value4" :format-tooltip="formatTooltip" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Disabled
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value5" disabled />
		</fb-col>
	</fb-row>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value1 = ref(0);
const value2 = ref(0);
const value3 = ref(0);
const value4 = ref(0);
const value5 = ref(0);

const formatTooltip = (val: number) => {
	return val / 100;
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSlider, FbRow, FbCol },
        setup: () => {
            const value1 = ref(0);
            const value2 = ref(0);
            const value3 = ref(0);
            const value4 = ref(0);
            const value5 = ref(0);

            const formatTooltip = (val: number) => {
                return val / 100;
            };

            return {
                value1,
                value2,
                value3,
                value4,
                value5,
                formatTooltip,
            };
        },
        template: `
<div class="fb-slider-story-block">
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Default value
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value1" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Customized initial value
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value2" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Hide Tooltip
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value3" :show-tooltip="false" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Format Tooltip
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value4" :format-tooltip="formatTooltip" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Disabled
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value5" disabled />
		</fb-col>
	</fb-row>
</div>`,
    }),
};

export const Discrete: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Breakpoints not displayed
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value1" :step="10" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Breakpoints displayed
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value2" :step="10" show-stops />
		</fb-col>
	</fb-row>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value1 = ref(0);
const value2 = ref(0);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSlider, FbRow, FbCol },
        setup: () => {
            const value1 = ref(0);
            const value2 = ref(0);

            return {
                value1,
                value2,
            };
        },
        template: `
<div class="fb-slider-story-block">
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Breakpoints not displayed
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value1" :step="10" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="6">
			Breakpoints displayed
		</fb-col>
		<fb-col :sm="12" :md="10">
			<fb-slider v-model="value2" :step="10" show-stops />
		</fb-col>
	</fb-row>
</div>`,
    }),
};

export const WithInput: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-slider v-model="value" show-input />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref(0);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSlider },
        setup: () => {
            const value = ref(0);

            return {
                value,
            };
        },
        template: `
<div class="fb-slider-story-block">
	<div class="fb-slider-story-block__item">
		<fb-slider v-model="value" show-input />
	</div>
</div>`,
    }),
};

export const Sizes: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-slider v-model="value" show-input size="${ComponentSizeTypes.LARGE}" />
	<fb-slider v-model="value" show-input />
	<fb-slider v-model="value" show-input size="${ComponentSizeTypes.SMALL}" />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref(0);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSlider },
        setup: () => {
            const value = ref(0);

            return {
                value,
            };
        },
        template: `
<div class="fb-slider-story-block">
	<div class="fb-slider-story-block__item">
		<fb-slider v-model="value" show-input size="${ComponentSizeTypes.LARGE}" />
	</div>
	<div class="fb-slider-story-block__item">
		<fb-slider v-model="value" show-input />
	</div>
	<div class="fb-slider-story-block__item">
		<fb-slider v-model="value" show-input size="${ComponentSizeTypes.SMALL}" />
	</div>
</div>`,
    }),
};

export const Variants: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="3">
			Primary
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-slider v-model="value1" variant="${VariantTypes.PRIMARY}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="3">
			Default
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-slider v-model="value2" variant="${VariantTypes.DEFAULT}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="3">
			Info
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-slider v-model="value3" :show-tooltip="false" variant="${VariantTypes.INFO}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="3">
			Success
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-slider v-model="value4" :format-tooltip="formatTooltip" variant="${VariantTypes.SUCCESS}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="3">
			Warning
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-slider v-model="value5" variant="${VariantTypes.WARNING}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="3">
			Danger
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-slider v-model="value6" variant="${VariantTypes.DANGER}" />
		</fb-col>
	</fb-row>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value1 = ref(0);
const value2 = ref(0);
const value3 = ref(0);
const value4 = ref(0);
const value5 = ref(0);
const value6 = ref(0);

const formatTooltip = (val: number) => {
	return val / 100;
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSlider, FbRow, FbCol },
        setup: () => {
            const value1 = ref(20);
            const value2 = ref(10);
            const value3 = ref(50);
            const value4 = ref(30);
            const value5 = ref(40);
            const value6 = ref(20);

            const formatTooltip = (val: number) => {
                return val / 100;
            };

            return {
                value1,
                value2,
                value3,
                value4,
                value5,
                value6,
                formatTooltip,
            };
        },
        template: `
<div class="fb-slider-story-block">
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="3">
			Primary
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-slider v-model="value1" variant="${VariantTypes.PRIMARY}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="3">
			Default
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-slider v-model="value2" variant="${VariantTypes.DEFAULT}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="3">
			Info
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-slider v-model="value3" :show-tooltip="false" variant="${VariantTypes.INFO}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="3">
			Success
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-slider v-model="value4" :format-tooltip="formatTooltip" variant="${VariantTypes.SUCCESS}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="3">
			Warning
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-slider v-model="value5" variant="${VariantTypes.WARNING}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="3">
			Danger
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-slider v-model="value6" variant="${VariantTypes.DANGER}" />
		</fb-col>
	</fb-row>
</div>`,
    }),
};

export const Placement: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-slider v-model="value1" />
	<fb-slider v-model="value2" placement="bottom" />
	<fb-slider v-model="value3" placement="right" />
	<fb-slider v-model="value4" placement="left" />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value1 = ref(10);
const value2 = ref(20);
const value3 = ref(30);
const value4 = ref(40);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSlider },
        setup: () => {
            const value1 = ref(10);
            const value2 = ref(20);
            const value3 = ref(30);
            const value4 = ref(40);

            return {
                value1,
                value2,
                value3,
                value4,
            };
        },
        template: `
<div class="fb-slider-story-block">
	<div class="fb-slider-story-block__item">
		<fb-slider v-model="value1" />
	</div>
	<div class="fb-slider-story-block__item">
		<fb-slider v-model="value2" placement="bottom" />
	</div>
	<div class="fb-slider-story-block__item">
		<fb-slider v-model="value3" placement="right" />
	</div>
	<div class="fb-slider-story-block__item">
		<fb-slider v-model="value4" placement="left" />
	</div>
</div>`,
    }),
};

export const RangeSelection: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-slider v-model="value" range show-stops :max="10" />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref([4, 8]);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSlider },
        setup: () => {
            const value = ref([4, 8]);

            return {
                value,
            };
        },
        template: `
<div class="fb-slider-story-block">
	<div class="fb-slider-story-block__item">
		<fb-slider v-model="value" range show-stops :max="10" />
	</div>
</div>`,
    }),
};

export const Vertical: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-slider v-model="value" vertical height="200px" />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref(0);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSlider },
        setup: () => {
            const value = ref(0);

            return {
                value,
            };
        },
        template: `
<div class="fb-slider-story-block">
	<div class="fb-slider-story-block__item">
		<fb-slider v-model="value" vertical height="200px" />
	</div>
</div>`,
    }),
};

export const Marks: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-slider v-model="value" range :marks="marks" />
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import type { CSSProperties } from 'vue';

interface Mark {
  style: CSSProperties
  label: string
}

type Marks = Record<number, Mark | string>;

const value = ref([30, 60]);

const marks = reactive<Marks>({
	0: '0°C',
	8: '8°C',
	37: '37°C',
	50: {
		style: {
			color: '#1989FA',
		},
		label: '50%',
	},
});
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSlider },
        setup: () => {
            interface Mark {
                style: CSSProperties;
                label: string;
            }

            type Marks = Record<number, Mark | string>;

            const value = ref([30, 60]);
            const marks = reactive<Marks>({
                0: "0°C",
                8: "8°C",
                37: "37°C",
                50: {
                    style: {
                        color: "#1989FA",
                    },
                    label: "50%",
                },
            });

            return {
                value,
                marks,
            };
        },
        template: `
<div class="fb-slider-story-block">
	<div class="fb-slider-story-block__item">
		<fb-slider v-model="value" range :marks="marks" />
	</div>
</div>`,
    }),
};
