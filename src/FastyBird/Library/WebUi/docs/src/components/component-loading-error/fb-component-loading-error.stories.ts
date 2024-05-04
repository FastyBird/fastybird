import { Meta, StoryObj } from "@storybook/vue3";
import { FbComponentLoadingError } from "@fastybird/web-ui-components";
import { ComponentSizeTypes } from "@fastybird/web-ui-constants";

const meta: Meta<typeof FbComponentLoadingError> = {
    component: FbComponentLoadingError,
    title: "Components/Feedback/Component loading error",
    argTypes: {
        size: {
            type: { name: "string", required: false },
            control: { type: "select" },
            options: [ComponentSizeTypes.LARGE, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.SMALL],
            description: "loading result size",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: ComponentSizeTypes.DEFAULT },
            },
        },
    },
    args: {
        size: ComponentSizeTypes.DEFAULT,
    },
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbComponentLoadingError>;

export const Component: Story = {};

export const Sizes: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-component-loading-error size="${ComponentSizeTypes.LARGE}" />
	<fb-component-loading-error />
	<fb-component-loading-error size="${ComponentSizeTypes.SMALL}" />
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbComponentLoadingError },
        template: `
<div style="max-width: 600px;">
	<div>
		<fb-component-loading-error size="${ComponentSizeTypes.LARGE}" />
	</div>
	<div style="margin-top: 20px;">
		<fb-component-loading-error />
	</div>
	<div style="margin-top: 20px;">
		<fb-component-loading-error size="${ComponentSizeTypes.SMALL}" />
	</div>
</div>`,
    }),
};
