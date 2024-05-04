import { Meta, StoryObj } from "@storybook/vue3";
import { action } from "@storybook/addon-actions";
import { FasWandMagicSparkles } from "@fastybird/web-ui-icons";
import { FbMediaItem, FbButton, FbIcon } from "@fastybird/web-ui-components";

import "./fb-media-item.stories.scss";

const meta: Meta<typeof FbMediaItem> = {
    component: FbMediaItem,
    title: "Components/Data/Media item",
    argTypes: {
        left: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "Item left box content slot",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        right: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "Item right box content slot",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        heading: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "Item heading slot",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        description: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "Item description slot",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        action: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "Item action slot",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
    },
    args: {},
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbMediaItem>;

export const Component: Story = {
    tags: ["hideInSidebar"],
    render: (args) => ({
        components: { FbMediaItem, FbButton, FbIcon, FasWandMagicSparkles },
        template: `
<fb-media-item>
	${args.left !== null && typeof args.left !== "undefined" ? `<template #left>${args.left}</template>` : ""}
	${args.right !== null && typeof args.right !== "undefined" ? `<template #right>${args.right}</template>` : ""}
	${args.heading !== null && typeof args.heading !== "undefined" ? `<template #heading>${args.heading}</template>` : ""}
	${args.description !== null && typeof args.description !== "undefined" ? `<template #description>${args.description}</template>` : ""}
	${args.action !== null && typeof args.action !== "undefined" ? `<template #action>${args.action}</template>` : ""}
</fb-media-item>`,
    }),
    args: {
        left: '<fb-icon size="35"><fas-wand-magic-sparkles /></fb-icon>',
        heading: "All created items",
        description: "Here could find all created items stored in database",
        action: "<fb-button>Reload</fb-button>",
    },
};

export const BasicUsage: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-media-item>
		<template #left>
			<fb-icon size="35">
				<fas-wand-magic-sparkles />
			</fb-icon>
		</template>
		<template #heading>
			All created items
		</template>
		<template #description>
			Here could find all created items stored in database
		</template>
		<template #action>
			<fb-button @click.prevent="handleClick">Reload</fb-button>
		</template>
	</fb-media-item>
</template>

<script lang="ts" setup>
const handleClick = (): void => {
	console.log('Button clicked');
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbMediaItem, FbButton, FbIcon, FasWandMagicSparkles },
        setup: () => {
            const onClick = action("button-clicked");

            return {
                onClick,
                FasWandMagicSparkles,
            };
        },
        template: `
<fb-media-item>
	<template #left>
		<fb-icon size="35">
			<fas-wand-magic-sparkles />
		</fb-icon>
	</template>
	<template #heading>
		All created items
	</template>
	<template #description>
		Here could find all created items stored in database
	</template>
	<template #action>
		<fb-button @click.prevent="onClick">Reload</fb-button>
	</template>
</fb-media-item>`,
    }),
};
