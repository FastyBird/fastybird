import { ref } from "vue";

import { Meta, StoryObj } from "@storybook/vue3";
import { FbMessage, FbSwitch, FbTooltip, FbRow, FbCol } from "@fastybird/web-ui-components";
import { FasXmark, FasCheck, FasEye, FasEyeSlash } from "@fastybird/web-ui-icons";
import { ComponentSizeTypes, VariantTypes } from "@fastybird/web-ui-constants";

import "./fb-switch.stories.scss";

const meta: Meta<typeof FbSwitch> = {
    component: FbSwitch,
    title: "Components/Form/Switch",
    argTypes: {
        disabled: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether switch is disabled",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        loading: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether switch is in loading state",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        size: {
            type: { name: "string", required: false },
            control: { type: "select" },
            options: [ComponentSizeTypes.LARGE, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.SMALL],
            description: "spinner size",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: ComponentSizeTypes.DEFAULT },
            },
        },
        width: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "width of switch",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        inlinePrompt: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether icon or text is displayed inside dot, only the first character will be rendered for text",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        activeText: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "text displayed when in `on` state",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        inactiveText: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "text displayed when in `off` state",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        activeValue: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "switch value when in `on` state",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: true },
            },
        },
        inactiveValue: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "switch value when in `off` state",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: false },
            },
        },
        name: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "input name of switch",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
    },
    args: {
        disabled: false,
        loading: false,
        size: ComponentSizeTypes.DEFAULT,
        inlinePrompt: false,
        activeValue: "true",
        inactiveValue: "false",
    },
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbSwitch>;

export const Component: Story = {
    tags: ["hideInSidebar"],
};

export const Basic: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-switch v-model="value1" />
	<fb-switch v-model="value2" class="switch-colors" />
</template>

<script setup lang="ts">
import { ref } from 'vue';

const value1 = ref(true);
const value2 = ref(true);
</script>

<style scoped>
.switch-colors {
	--fb-switch-on-color: #13ce66;
	--fb-switch-off-color: #ff4949
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSwitch },
        setup: () => {
            const value1 = ref(true);
            const value2 = ref(true);

            return {
                value1,
                value2,
            };
        },
        template: `
<div class="fb-switch-story-block">
	<fb-switch v-model="value1" class="fb-switch-story-block__row-item" />
	<fb-switch v-model="value2"  class="switch-colors fb-switch-story-block__row-item" />
</div>`,
    }),
};

export const Sizes: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-switch
		v-model="value"
		size="large"
		active-text="Open"
		inactive-text="Close"
	/>

	<fb-switch v-model="value" active-text="Open" inactive-text="Close" />

	<fb-switch
		v-model="value"
		size="small"
		active-text="Open"
		inactive-text="Close"
	/>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const value = ref(true);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSwitch },
        setup: () => {
            const value = ref(true);

            return {
                value,
            };
        },
        template: `
<div class="fb-switch-story-block">
	<div class="fb-switch-story-block__item">
		<fb-switch
			v-model="value"
			size="large"
			active-text="Open"
			inactive-text="Close"
		/>
	</div>
	<div class="fb-switch-story-block__item">
		<fb-switch v-model="value" active-text="Open" inactive-text="Close" />
	</div>
	<div class="fb-switch-story-block__item">
		<fb-switch
			v-model="value"
			size="small"
			active-text="Open"
			inactive-text="Close"
		/>
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
		<fb-col :sm="12" :md="4">
			Primary
		</fb-col>
		<fb-col :sm="12" :md="20">
			<fb-switch v-model="value" active-text="Open" inactive-text="Close" variant="${VariantTypes.PRIMARY}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="4">
			Default
		</fb-col>
		<fb-col :sm="12" :md="20">
			<fb-switch v-model="value" active-text="Open" inactive-text="Close" variant="${VariantTypes.DEFAULT}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="4">
			Info
		</fb-col>
		<fb-col :sm="12" :md="20">
			<fb-switch v-model="value" active-text="Open" inactive-text="Close" variant="${VariantTypes.INFO}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="4">
			Success
		</fb-col>
		<fb-col :sm="12" :md="20">
			<fb-switch v-model="value" active-text="Open" inactive-text="Close" variant="${VariantTypes.SUCCESS}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="4">
			Warning
		</fb-col>
		<fb-col :sm="12" :md="20">
			<fb-switch v-model="value" active-text="Open" inactive-text="Close" variant="${VariantTypes.WARNING}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="4">
			Danger
		</fb-col>
		<fb-col :sm="12" :md="20">
			<fb-switch v-model="value" active-text="Open" inactive-text="Close" variant="${VariantTypes.DANGER}" />
		</fb-col>
	</fb-row>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const value = ref(true);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSwitch, FbRow, FbCol },
        setup: () => {
            const value = ref(true);

            return {
                value,
            };
        },
        template: `
<div class="fb-switch-story-block">
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="4">
			Primary
		</fb-col>
		<fb-col :sm="12" :md="20">
			<fb-switch v-model="value" active-text="Open" inactive-text="Close" variant="${VariantTypes.PRIMARY}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="4">
			Default
		</fb-col>
		<fb-col :sm="12" :md="20">
			<fb-switch v-model="value" active-text="Open" inactive-text="Close" variant="${VariantTypes.DEFAULT}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="4">
			Info
		</fb-col>
		<fb-col :sm="12" :md="20">
			<fb-switch v-model="value" active-text="Open" inactive-text="Close" variant="${VariantTypes.INFO}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="4">
			Success
		</fb-col>
		<fb-col :sm="12" :md="20">
			<fb-switch v-model="value" active-text="Open" inactive-text="Close" variant="${VariantTypes.SUCCESS}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="4">
			Warning
		</fb-col>
		<fb-col :sm="12" :md="20">
			<fb-switch v-model="value" active-text="Open" inactive-text="Close" variant="${VariantTypes.WARNING}" />
		</fb-col>
	</fb-row>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="4">
			Danger
		</fb-col>
		<fb-col :sm="12" :md="20">
			<fb-switch v-model="value" active-text="Open" inactive-text="Close" variant="${VariantTypes.DANGER}" />
		</fb-col>
	</fb-row>
</div>`,
    }),
};

export const TextDescription: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-switch
		v-model="value1"
		active-text="Automate mode"
		inactive-text="Manual mode"
	/>

	<fb-switch
		v-model="value2"
		class="switch-colors"
		active-text="Automate mode"
		inactive-text="Manual mode"
	/>

	<fb-switch
		v-model="value3"
		inline-prompt
		active-text="Yes"
		inactive-text="No"
	/>

	<fb-switch
		v-model="value4"
		inline-prompt
		class="switch-colors"
		active-text="Y"
		inactive-text="N"
	/>

	<fb-switch
		v-model="value5"
		width="60"
		inline-prompt
		active-text="On"
		inactive-text="Off"
	/>

	<fb-switch
		v-model="value6"
		inline-prompt
		class="switch-colors"
		active-text="Switch On"
		inactive-text="Switch Off"
	/>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const value1 = ref(true);
const value2 = ref(true);
const value3 = ref(true);
const value4 = ref(true);
const value5 = ref(true);
const value6 = ref(true);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSwitch },
        setup: () => {
            const value1 = ref(true);
            const value2 = ref(true);
            const value3 = ref(true);
            const value4 = ref(true);
            const value5 = ref(true);
            const value6 = ref(true);

            return {
                value1,
                value2,
                value3,
                value4,
                value5,
                value6,
            };
        },
        template: `
<div class="fb-switch-story-block">
	<div class="fb-switch-story-block__item">
		<fb-switch
			v-model="value1"
			active-text="Automate mode"
			inactive-text="Manual mode"
		/>
	</div>
	<div class="fb-switch-story-block__item">
		<fb-switch
			v-model="value2"
			class="switch-colors"
			active-text="Automate mode"
			inactive-text="Manual mode"
		/>
	</div>
	<div class="fb-switch-story-block__item">
		<fb-switch
			v-model="value3"
			inline-prompt
			class="fb-switch-story-block__row-item"
			active-text="Yes"
			inactive-text="No"
		/>
		<fb-switch
			v-model="value4"
			inline-prompt
			class="switch-colors fb-switch-story-block__row-item"
			active-text="Y"
			inactive-text="N"
		/>
		<fb-switch
			v-model="value5"
			width="60"
			inline-prompt
			active-text="On"
			inactive-text="Off"
			class="fb-switch-story-block__row-item"
		/>
		<fb-switch
			v-model="value6"
			inline-prompt
			class="switch-colors fb-switch-story-block__row-item"
			active-text="Switch On"
			inactive-text="Switch Off"
		/>
	</div>
</div>`,
    }),
};

export const CustomIcons: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-switch v-model="value1" :active-icon="FasCheck" :inactive-icon="FasXmark" class="fb-switch-story-block__row-item" />

	<fb-switch v-model="value2" inline-prompt :active-icon="FasCheck" :inactive-icon="FasXmark" class="fb-switch-story-block__row-item" />
</template>

<script setup lang="ts">
import { ref } from 'vue';

const value1 = ref(true);
const value2 = ref(true);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSwitch },
        setup: () => {
            const value1 = ref(true);
            const value2 = ref(true);

            return {
                FasXmark,
                FasCheck,
                value1,
                value2,
            };
        },
        template: `
<div class="fb-switch-story-block">
	<div class="fb-switch-story-block__item">
		<fb-switch v-model="value1" :active-icon="FasCheck" :inactive-icon="FasXmark" />
	</div>
	<div class="fb-switch-story-block__item">
		<fb-switch v-model="value2" inline-prompt :active-icon="FasCheck" :inactive-icon="FasXmark" />
	</div>
</div>`,
    }),
};

export const CustomActionIcons: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-switch
		v-model="value"
		:active-action-icon="FasEye"
		:inactive-action-icon="FasEyeSlash"
	/>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { FasEye, FasEyeSlash } from '@fastybird/web-ui-icons';

const value = ref(true);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSwitch },
        setup: () => {
            const value = ref(true);

            return {
                FasEye,
                FasEyeSlash,
                value,
            };
        },
        template: `
<fb-switch
	v-model="value"
	:active-action-icon="FasEye"
	:inactive-action-icon="FasEyeSlash"
/>`,
    }),
};

export const ValueTypes: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-tooltip :content="'Switch value: ' + value" placement="top">
		<fb-switch
			v-model="value"
			class="switch-colors"
			active-value="100"
			inactive-value="0"
		/>
	</fb-tooltip>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const value = ref('100');
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSwitch, FbTooltip },
        setup: () => {
            const value = ref("100");

            return {
                value,
            };
        },
        template: `
<div class="fb-switch-story-block">
	<fb-tooltip :content="'Switch value: ' + value" placement="top">
		<fb-switch
			v-model="value"
			class="switch-colors"
			active-value="100"
			inactive-value="0"
		/>
	</fb-tooltip>
</div>`,
    }),
};

export const Disabled: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-switch v-model="value1" disabled />
	<fb-switch v-model="value2" />
</template>

<script setup lang="ts">
import { ref } from 'vue';

const value1 = ref(true);
const value2 = ref(true);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSwitch },
        setup: () => {
            const value1 = ref(true);
            const value2 = ref(true);

            return {
                value1,
                value2,
            };
        },
        template: `
<div class="fb-switch-story-block">
	<fb-switch v-model="value1" disabled class="fb-switch-story-block__row-item" />
	<fb-switch v-model="value2" class="fb-switch-story-block__row-item" />
</div>`,
    }),
};

export const Loading: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-switch v-model="value1" loading />
	<fb-switch v-model="value2" loading />
</template>

<script setup lang="ts">
import { ref } from 'vue';

const value1 = ref(true);
const value2 = ref(false);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSwitch },
        setup: () => {
            const value1 = ref(true);
            const value2 = ref(false);

            return {
                value1,
                value2,
            };
        },
        template: `
<div class="fb-switch-story-block">
	<fb-switch v-model="value1" loading class="fb-switch-story-block__row-item" />
	<fb-switch v-model="value2" loading class="fb-switch-story-block__row-item" />
</div>`,
    }),
};

export const PreventSwitching: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-switch
		v-model="value1"
		:loading="loading1"
		:before-change="beforeChange1"
	/>

	<fb-switch
		v-model="value2"
		:loading="loading2"
		:before-change="beforeChange2"
	/>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const value1 = ref(true);
const value2 = ref(false);
const loading1 = ref(false);
const loading2 = ref(false);

const beforeChange1 = (): Promise<boolean> => {
	loading1.value = true;

	return new Promise((resolve): void => {
		setTimeout((): void => {
			loading1.value = false;

			FbMessage.success('Switch success');

			resolve(true);
		}, 1000);
	});
};

const beforeChange2 = (): Promise<boolean> => {
	loading2.value = true;

	return new Promise((_, reject): void => {
		setTimeout((): void => {
			loading2.value = false;

			FbMessage.error('Switch failed');

			reject(new Error('Error'));
		}, 1000);
	});
};
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSwitch },
        setup: () => {
            const value1 = ref(true);
            const value2 = ref(false);
            const loading1 = ref(false);
            const loading2 = ref(false);

            const beforeChange1 = (): Promise<boolean> => {
                loading1.value = true;

                return new Promise((resolve): void => {
                    setTimeout((): void => {
                        loading1.value = false;

                        FbMessage.success("Switch success");

                        resolve(true);
                    }, 1000);
                });
            };

            const beforeChange2 = (): Promise<boolean> => {
                loading2.value = true;

                return new Promise((_, reject): void => {
                    setTimeout((): void => {
                        loading2.value = false;

                        FbMessage.error("Switch failed");

                        reject(new Error("Error"));
                    }, 1000);
                });
            };

            return {
                value1,
                value2,
                loading1,
                loading2,
                beforeChange1,
                beforeChange2,
            };
        },
        template: `
<div class="fb-switch-story-block">
	<fb-switch
		v-model="value1"
		:loading="loading1"
		:before-change="beforeChange1"
		class="fb-switch-story-block__row-item"
	/>
	<fb-switch
		v-model="value2"
		:loading="loading2"
		:before-change="beforeChange2"
		class="fb-switch-story-block__row-item"
	/>
</div>`,
    }),
};

export const CustomActionSlot: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-switch v-model="value">
		<template #active-action>
			T
		</template>
		<template #inactive-action>
			F
		</template>
	</fb-switch>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const value = ref(true);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSwitch },
        setup: () => {
            const value = ref(true);

            return {
                value,
            };
        },
        template: `
<div class="fb-switch-story-block">
	<fb-switch v-model="value">
		<template #active-action>
			T
		</template>
		<template #inactive-action>
			F
		</template>
	</fb-switch>
</div>`,
    }),
};
