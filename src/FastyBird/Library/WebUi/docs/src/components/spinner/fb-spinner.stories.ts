import { Meta, StoryObj } from "@storybook/vue3";
import { FbSpinner } from "@fastybird/web-ui-components";
import { ComponentSizeTypes, VariantTypes } from "@fastybird/web-ui-constants";

import "./fb-spinner.stories.scss";

const meta: Meta<typeof FbSpinner> = {
    component: FbSpinner,
    title: "Components/Feedback/Spinner",
    argTypes: {
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
        variant: {
            type: { name: "string", required: false },
            control: { type: "select" },
            options: [VariantTypes.DEFAULT, VariantTypes.PRIMARY, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
            description: "spinner variant",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: VariantTypes.DEFAULT },
            },
        },
        bg: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "determine whether the spinner background color is always on",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
    },
    args: {
        size: ComponentSizeTypes.DEFAULT,
        variant: VariantTypes.DEFAULT,
        bg: true,
    },
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbSpinner>;

export const Component: Story = {
    tags: ["hideInSidebar"],
};

export const Sizes: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-spinner size="${ComponentSizeTypes.LARGE}" />
	<fb-spinner />
	<fb-spinner size="${ComponentSizeTypes.SMALL}" />
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSpinner },
        template: `
<div class="fb-spinner-story-block">
	<div class="fb-spinner-story-block__item">
		<fb-spinner size="${ComponentSizeTypes.LARGE}" />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner size="${ComponentSizeTypes.SMALL}" />
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
	<fb-spinner variant="${VariantTypes.PRIMARY}" />
	<fb-spinner variant="${VariantTypes.DEFAULT}" />
	<fb-spinner variant="${VariantTypes.INFO}" />
	<fb-spinner variant="${VariantTypes.SUCCESS}" />
	<fb-spinner variant="${VariantTypes.WARNING}" />
	<fb-spinner variant="${VariantTypes.DANGER}" />
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSpinner },
        template: `
<div class="fb-spinner-story-block">
	<div class="fb-spinner-story-block__item">
		<fb-spinner variant="${VariantTypes.PRIMARY}" />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner variant="${VariantTypes.DEFAULT}" />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner variant="${VariantTypes.INFO}" />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner variant="${VariantTypes.SUCCESS}" />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner variant="${VariantTypes.WARNING}" />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner variant="${VariantTypes.DANGER}" />
	</div>
</div>`,
    }),
};
