import { nextTick, ref } from "vue";

import { Meta, StoryObj } from "@storybook/vue3";
import { FbTag, FbCheckTag, FbButton, FbInput, TagProps } from "@fastybird/web-ui-components";
import { ComponentSizeTypes, EffectTypes, VariantTypes } from "@fastybird/web-ui-constants";

import "./fb-tag.stories.scss";

import type { Ref } from "vue";

const meta: Meta<typeof FbTag> = {
    component: FbTag,
    title: "Components/Data/Tag",
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbTag>;

export const Component: Story = {
    tags: ["hideInSidebar"],
};

export const BasicUsage: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-tag variant="${VariantTypes.PRIMARY}">Tag 1</fb-tag>
	<fb-tag variant="${VariantTypes.INFO}">Tag 2</fb-tag>
	<fb-tag variant="${VariantTypes.SUCCESS}">Tag 3</fb-tag>
	<fb-tag variant="${VariantTypes.WARNING}">Tag 4</fb-tag>
	<fb-tag variant="${VariantTypes.DANGER}">Tag 5</fb-tag>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbTag },
        template: `
<div class="fb-tag-story-block">
	<div class="fb-tag-story-block__item">
		<fb-tag variant="${VariantTypes.PRIMARY}">Tag 1</fb-tag>
		<fb-tag variant="${VariantTypes.INFO}">Tag 2</fb-tag>
		<fb-tag variant="${VariantTypes.SUCCESS}">Tag 3</fb-tag>
		<fb-tag variant="${VariantTypes.WARNING}">Tag 4</fb-tag>
		<fb-tag variant="${VariantTypes.DANGER}">Tag 5</fb-tag>
	</div>
</div>`,
    }),
};

export const Removable: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-tag variant="${VariantTypes.PRIMARY}" closable>Tag 1</fb-tag>
	<fb-tag variant="${VariantTypes.INFO}" closable>Tag 2</fb-tag>
	<fb-tag variant="${VariantTypes.SUCCESS}" closable>Tag 3</fb-tag>
	<fb-tag variant="${VariantTypes.WARNING}" closable>Tag 4</fb-tag>
	<fb-tag variant="${VariantTypes.DANGER}" closable>Tag 5</fb-tag>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbTag },
        template: `
<div class="fb-tag-story-block">
	<div class="fb-tag-story-block__item">
		<fb-tag variant="${VariantTypes.PRIMARY}" closable>Tag 1</fb-tag>
		<fb-tag variant="${VariantTypes.INFO}" closable>Tag 2</fb-tag>
		<fb-tag variant="${VariantTypes.SUCCESS}" closable>Tag 3</fb-tag>
		<fb-tag variant="${VariantTypes.WARNING}" closable>Tag 4</fb-tag>
		<fb-tag variant="${VariantTypes.DANGER}" closable>Tag 5</fb-tag>
	</div>
</div>`,
    }),
};

export const Dynamical: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-tag
		v-for="tag in dynamicTags"
		:key="tag"
		closable
		:disable-transitions="false"
		@close="handleClose(tag)"
	>
		{{ tag }}
	</fb-tag>

	<fb-input
		v-if="inputVisible"
		ref="InputRef"
		v-model="inputValue"
		size="${ComponentSizeTypes.SMALL}"
		@keyup.enter="handleInputConfirm"
		@blur="handleInputConfirm"
	/>

	<fb-button v-else size="${ComponentSizeTypes.SMALL}" @click="showInput">
		+ New Tag
	</fb-button>
</template>

<script lang="ts" setup>
import { nextTick, ref } from 'vue';
import { FbButton, FbInput } from '@fastybird/web-ui-components';

const inputValue = ref('');
const dynamicTags = ref(['Tag 1', 'Tag 2', 'Tag 3']);
const inputVisible = ref(false);
const InputRef = ref<InstanceType<typeof FbInput>>();

const handleClose = (tag: string) => {
	dynamicTags.value.splice(dynamicTags.value.indexOf(tag), 1);
}

const showInput = () => {
	inputVisible.value = true

	nextTick(() => {
		InputRef.value!.input!.focus()
	});
}

const handleInputConfirm = () => {
	if (inputValue.value) {
		dynamicTags.value.push(inputValue.value);
	}

	inputVisible.value = false;
	inputValue.value = '';
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbTag, FbButton, FbInput },
        setup: (): {
            inputValue: Ref<string>;
            dynamicTags: Ref<string[]>;
            inputVisible: Ref<boolean>;
            InputRef: Ref<InstanceType<typeof FbInput>>;
            handleClose: () => void;
            showInput: () => void;
            handleInputConfirm: () => void;
        } => {
            const inputValue = ref("");
            const dynamicTags = ref(["Tag 1", "Tag 2", "Tag 3"]);
            const inputVisible = ref(false);
            const InputRef = ref<InstanceType<typeof FbInput>>();

            const handleClose = (tag: string): void => {
                dynamicTags.value.splice(dynamicTags.value.indexOf(tag), 1);
            };

            const showInput = async (): Promise<void> => {
                inputVisible.value = true;

                await nextTick();

                InputRef.value!.input!.focus();
            };

            const handleInputConfirm = (): void => {
                if (inputValue.value) {
                    dynamicTags.value.push(inputValue.value);
                }

                inputVisible.value = false;
                inputValue.value = "";
            };

            return {
                inputValue,
                dynamicTags,
                inputVisible,
                InputRef,
                handleClose,
                showInput,
                handleInputConfirm,
            };
        },
        template: `
<div class="fb-tag-story-block">
	<div class="fb-tag-story-block__item">
		<fb-tag
			v-for="tag in dynamicTags"
			:key="tag"
			closable
			:disable-transitions="false"
			@close="handleClose(tag)"
		>
			{{ tag }}
		</fb-tag>

		<fb-input
			v-if="inputVisible"
			ref="InputRef"
			v-model="inputValue"
			size="${ComponentSizeTypes.SMALL}"
			@keyup.enter="handleInputConfirm"
			@blur="handleInputConfirm"
		/>

		<fb-button v-else size="${ComponentSizeTypes.SMALL}" @click="showInput">
			+ New Tag
		</fb-button>
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
	<fb-tag size="${ComponentSizeTypes.LARGE}">Large</fb-tag>
	<fb-tag>Default</fb-tag>
	<fb-tag size="${ComponentSizeTypes.SMALL}">Small</fb-tag>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbTag },
        template: `
<div class="fb-tag-story-block">
	<div class="fb-tag-story-block__item">
		<fb-tag size="${ComponentSizeTypes.LARGE}">Large</fb-tag>
		<fb-tag>Default</fb-tag>
		<fb-tag size="${ComponentSizeTypes.SMALL}">Small</fb-tag>
	</div>
</div>`,
    }),
};

export const Theme: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<div>
		<span>Dark</span>
		<fb-tag
			v-for="item in items"
			:key="item.label"
			:variant="item.variant"
			effect="${EffectTypes.DARK}"
		>
			{{ item.label }}
		</fb-tag>
	</div>
	<div>
		<span>Light</span>
		<fb-tag
			v-for="item in items"
			:key="item.label"
			:variant="item.variant"
			effect="${EffectTypes.LIGHT}"
		>
			{{ item.label }}
		</fb-tag>
	</div>
	<div>
		<span>Plain</span>
		<fb-tag
			v-for="item in items"
			:key="item.label"
			:variant="item.variant"
			effect="${EffectTypes.PLAIN}"
		>
			{{ item.label }}
		</fb-tag>
	</div>
</template>

<script lang="ts" setup>
import { nextTick, ref } from 'vue';
import type { TagProps } from '@fastybird/web-ui-components'

type Item = { variant: TagProps['variant']; label: string };

const items = ref<Array<Item>>([
	{ variant: VariantTypes.PRIMARY, label: 'Tag 1' },
	{ variant: VariantTypes.INFO, label: 'Tag 2' },
	{ variant: VariantTypes.SUCCESS, label: 'Tag 3' },
	{ variant: VariantTypes.WARNING, label: 'Tag 4' },
	{ variant: VariantTypes.DANGER, label: 'Tag 5' },
]);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbTag, FbButton, FbInput },
        setup: (): {
            items: Ref<Array<{ variant: TagProps["variant"]; label: string }>>;
        } => {
            type Item = { variant: TagProps["variant"]; label: string };

            const items = ref<Array<Item>>([
                { variant: VariantTypes.PRIMARY, label: "Tag 1" },
                { variant: VariantTypes.INFO, label: "Tag 2" },
                { variant: VariantTypes.SUCCESS, label: "Tag 3" },
                { variant: VariantTypes.WARNING, label: "Tag 4" },
                { variant: VariantTypes.DANGER, label: "Tag 5" },
            ]);

            return {
                items,
            };
        },
        template: `
<div class="fb-tag-story-block">
	<div class="fb-tag-story-block__item">
		<span>Dark</span>
		<fb-tag
			v-for="item in items"
			:key="item.label"
			:variant="item.variant"
			effect="${EffectTypes.DARK}"
		>
			{{ item.label }}
		</fb-tag>
	</div>
	<div class="fb-tag-story-block__item">
		<span>Light</span>
		<fb-tag
			v-for="item in items"
			:key="item.label"
			:variant="item.variant"
			effect="${EffectTypes.LIGHT}"
		>
			{{ item.label }}
		</fb-tag>
	</div>
	<div class="fb-tag-story-block__item">
		<span>Plain</span>
		<fb-tag
			v-for="item in items"
			:key="item.label"
			:variant="item.variant"
			effect="${EffectTypes.PLAIN}"
		>
			{{ item.label }}
		</fb-tag>
	</div>
</div>`,
    }),
};

export const Rounded: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<div>
		<fb-tag
			v-for="item in items"
			:key="item.label"
			:variant="item.variant"
			effect="${EffectTypes.DARK}"
			round
		>
			{{ item.label }}
		</fb-tag>
	</div>
	<div>
		<fb-tag
			v-for="item in items"
			:key="item.label"
			:variant="item.variant"
			effect="${EffectTypes.LIGHT}"
			round
		>
			{{ item.label }}
		</fb-tag>
	</div>
	<div>
		<fb-tag
			v-for="item in items"
			:key="item.label"
			:variant="item.variant"
			effect="${EffectTypes.PLAIN}"
			round
		>
			{{ item.label }}
		</fb-tag>
	</div>
</template>

<script lang="ts" setup>
import { nextTick, ref } from 'vue';
import type { TagProps } from '@fastybird/web-ui-components'

type Item = { variant: TagProps['variant']; label: string };

const items = ref<Array<Item>>([
	{ variant: VariantTypes.PRIMARY, label: 'Tag 1' },
	{ variant: VariantTypes.INFO, label: 'Tag 2' },
	{ variant: VariantTypes.SUCCESS, label: 'Tag 3' },
	{ variant: VariantTypes.WARNING, label: 'Tag 4' },
	{ variant: VariantTypes.DANGER, label: 'Tag 5' },
]);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbTag },
        setup: (): {
            items: Ref<Array<{ variant: TagProps["variant"]; label: string }>>;
        } => {
            type Item = { variant: TagProps["variant"]; label: string };

            const items = ref<Array<Item>>([
                { variant: VariantTypes.PRIMARY, label: "Tag 1" },
                { variant: VariantTypes.INFO, label: "Tag 2" },
                { variant: VariantTypes.SUCCESS, label: "Tag 3" },
                { variant: VariantTypes.WARNING, label: "Tag 4" },
                { variant: VariantTypes.DANGER, label: "Tag 5" },
            ]);

            return {
                items,
            };
        },
        template: `
<div class="fb-tag-story-block">
	<div class="fb-tag-story-block__item">
		<fb-tag
			v-for="item in items"
			:key="item.label"
			:variant="item.variant"
			effect="${EffectTypes.DARK}"
			round
		>
			{{ item.label }}
		</fb-tag>
	</div>
	<div class="fb-tag-story-block__item">
		<fb-tag
			v-for="item in items"
			:key="item.label"
			:variant="item.variant"
			effect="${EffectTypes.LIGHT}"
			round
		>
			{{ item.label }}
		</fb-tag>
	</div>
	<div class="fb-tag-story-block__item">
		<fb-tag
			v-for="item in items"
			:key="item.label"
			:variant="item.variant"
			effect="${EffectTypes.PLAIN}"
			round
		>
			{{ item.label }}
		</fb-tag>
	</div>
</div>`,
    }),
};

export const Checkable: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<div>
		<fb-check-tag checked>Checked</fb-check-tag>
		<fb-check-tag :checked="checked" @change="onChange">Toggle me</fb-check-tag>
	</div>
	<div>
		<fb-check-tag :checked="checked1" type="primary" @change="onChange1">
			Tag 1
		</fb-check-tag>
		<fb-check-tag :checked="checked2" type="success" @change="onChange2">
			Tag 2
		</fb-check-tag>
		<fb-check-tag :checked="checked3" type="info" @change="onChange3">
			Tag 3
		</fb-check-tag>
		<fb-check-tag :checked="checked4" type="warning" @change="onChange4">
			Tag 4
		</fb-check-tag>
		<fb-check-tag :checked="checked5" type="danger" @change="onChange5">
			Tag 5
		</fb-check-tag>
	</div>
</template>

<script lang="ts" setup>
import { nextTick, ref } from 'vue';

const checked = ref(false);
const checked1 = ref(true);
const checked2 = ref(true);
const checked3 = ref(true);
const checked4 = ref(true);
const checked5 = ref(true);

const onChange = (status: boolean): void => {
  checked.value = status;
}

const onChange1 = (status: boolean): void => {
  checked1.value = status;
}

const onChange2 = (status: boolean): void => {
  checked2.value = status;
}

const onChange3 = (status: boolean): void => {
  checked3.value = status;
}

const onChange4 = (status: boolean): void => {
  checked4.value = status;
}

const onChange5 = (status: boolean): void => {
  checked5.value = status;
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbCheckTag },
        setup: (): {
            checked: Ref<boolean>;
            checked1: Ref<boolean>;
            checked2: Ref<boolean>;
            checked3: Ref<boolean>;
            checked4: Ref<boolean>;
            checked5: Ref<boolean>;
            onChange: (status: boolean) => void;
            onChange1: (status: boolean) => void;
            onChange2: (status: boolean) => void;
            onChange3: (status: boolean) => void;
            onChange4: (status: boolean) => void;
            onChange5: (status: boolean) => void;
        } => {
            const checked = ref(false);
            const checked1 = ref(true);
            const checked2 = ref(true);
            const checked3 = ref(true);
            const checked4 = ref(true);
            const checked5 = ref(true);

            const onChange = (status: boolean): void => {
                checked.value = status;
            };

            const onChange1 = (status: boolean): void => {
                checked1.value = status;
            };

            const onChange2 = (status: boolean): void => {
                checked2.value = status;
            };

            const onChange3 = (status: boolean): void => {
                checked3.value = status;
            };

            const onChange4 = (status: boolean): void => {
                checked4.value = status;
            };

            const onChange5 = (status: boolean): void => {
                checked5.value = status;
            };

            return {
                checked,
                checked1,
                checked2,
                checked3,
                checked4,
                checked5,
                onChange,
                onChange1,
                onChange2,
                onChange3,
                onChange4,
                onChange5,
            };
        },
        template: `
<div class="fb-tag-story-block">
	<div class="fb-tag-story-block__item">
		<fb-check-tag checked>Checked</fb-check-tag>
		<fb-check-tag :checked="checked" @change="onChange">Toggle me</fb-check-tag>
	</div>
	<div class="fb-tag-story-block__item">
		<fb-check-tag :checked="checked1" type="primary" @change="onChange1">
			Tag 1
		</fb-check-tag>
		<fb-check-tag :checked="checked2" type="success" @change="onChange2">
			Tag 2
		</fb-check-tag>
		<fb-check-tag :checked="checked3" type="info" @change="onChange3">
			Tag 3
		</fb-check-tag>
		<fb-check-tag :checked="checked4" type="warning" @change="onChange4">
			Tag 4
		</fb-check-tag>
		<fb-check-tag :checked="checked5" type="danger" @change="onChange5">
			Tag 5
		</fb-check-tag>
	</div>
</div>`,
    }),
};
