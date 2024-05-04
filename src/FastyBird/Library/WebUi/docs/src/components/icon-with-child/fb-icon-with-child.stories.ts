import { Meta, StoryObj } from "@storybook/vue3";
import { FasBell, FasEnvelope } from "@fastybird/web-ui-icons";
import { FbIconWithChild } from "@fastybird/web-ui-components";
import { VariantTypes } from "@fastybird/web-ui-constants";

import "./fb-icon-with-child.stories.scss";

const meta: Meta<typeof FbIconWithChild> = {
    component: FbIconWithChild,
    title: "Components/Basic/Icon with child",
    argTypes: {
        variant: {
            type: { name: "string", required: false },
            control: { type: "select" },
            options: [VariantTypes.DEFAULT, VariantTypes.PRIMARY, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.DANGER],
            description: "child icon variant",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: VariantTypes.DEFAULT },
            },
        },
        size: {
            type: { name: "number", required: false },
            control: { type: "text" },
            description: "main icon size",
            table: {
                type: { summary: "number" },
                defaultValue: { summary: "-" },
            },
        },
        color: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "main icon color",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
    },
    args: {
        variant: VariantTypes.DEFAULT,
    },
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbIconWithChild>;

export const BasicUsage: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-icon-with-child :size="50">
		<template #icon><fas-envelope class="icon" /></template>
		<template #child><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="50" variant="${VariantTypes.PRIMARY}">
		<template #icon><fas-envelope class="icon" /></template>
		<template #child><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="50" variant="${VariantTypes.INFO}">
		<template #icon><fas-envelope class="icon" /></template>
		<template #child><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="50" variant="${VariantTypes.SUCCESS}">
		<template #icon><fas-envelope class="icon" /></template>
		<template #child><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="50" variant="${VariantTypes.WARNING}">
		<template #icon><fas-envelope class="icon" /></template>
		<template #child><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="50" variant="${VariantTypes.DANGER}">
		<template #icon><fas-envelope class="icon" /></template>
		<template #child><fas-bell /></template>
	</fb-icon-with-child>
</template>

<style scoped>
.icon {
	color: var(--fb-color-default-light-5);
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbIconWithChild, FasBell, FasEnvelope },
        template: `
<div class="fb-icon-with-child-story-block">
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50">
			<template #icon><fas-envelope class="icon-color" /></template>
			<template #child><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50" variant="${VariantTypes.PRIMARY}">
			<template #icon><fas-envelope class="icon-color" /></template>
			<template #child><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50" variant="${VariantTypes.INFO}">
			<template #icon><fas-envelope class="icon-color" /></template>
			<template #child><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50" variant="${VariantTypes.SUCCESS}">
			<template #icon><fas-envelope class="icon-color" /></template>
			<template #child><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50" variant="${VariantTypes.WARNING}">
			<template #icon><fas-envelope class="icon-color" /></template>
			<template #child><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50" variant="${VariantTypes.DANGER}">
			<template #icon><fas-envelope class="icon-color" /></template>
			<template #child><fas-bell /></template>
		</fb-icon-with-child>
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
	<fb-icon-with-child :size="80" variant="${VariantTypes.PRIMARY}">
		<template #icon><fas-envelope class="icon" /></template>
		<template #child><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="50" variant="${VariantTypes.PRIMARY}">
		<template #icon><fas-envelope class="icon" /></template>
		<template #child><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="40" variant="${VariantTypes.PRIMARY}">
		<template #icon><fas-envelope class="icon" /></template>
		<template #child><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="30" variant="${VariantTypes.PRIMARY}">
		<template #icon><fas-envelope class="icon" /></template>
		<template #child><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child variant="${VariantTypes.PRIMARY}">
		<template #icon><fas-envelope class="icon" /></template>
		<template #child><fas-bell /></template>
	</fb-icon-with-child>
</template>

<style scoped>
.icon {
	color: var(--fb-color-default-light-5);
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbIconWithChild, FasBell, FasEnvelope },
        template: `
<div class="fb-icon-with-child-story-block">
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="80" variant="${VariantTypes.PRIMARY}">
			<template #icon><fas-envelope class="icon-color" /></template>
			<template #child><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50" variant="${VariantTypes.PRIMARY}">
			<template #icon><fas-envelope class="icon-color" /></template>
			<template #child><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="40" variant="${VariantTypes.PRIMARY}">
			<template #icon><fas-envelope class="icon-color" /></template>
			<template #child><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="30" variant="${VariantTypes.PRIMARY}">
			<template #icon><fas-envelope class="icon-color" /></template>
			<template #child><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child variant="${VariantTypes.PRIMARY}">
			<template #icon><fas-envelope class="icon-color" /></template>
			<template #child><fas-bell /></template>
		</fb-icon-with-child>
	</div>
</div>`,
    }),
};
