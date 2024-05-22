import { Meta, StoryObj } from "@storybook/vue3";
import { FasChevronRight } from "@fastybird/web-ui-icons";
import { FbBreadcrumb, FbBreadcrumbItem } from "@fastybird/web-ui-components";

import "./fb-breadcrumb.stories.scss";

const meta: Meta<typeof FbBreadcrumb> = {
    component: FbBreadcrumb,
    title: "Components/Navigation/Breadcrumb",
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbBreadcrumb>;

export const BasicUsage: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-breadcrumb separator="/">
		<fb-breadcrumb-item :to="{ path: '/' }">homepage</fb-breadcrumb-item>
		<fb-breadcrumb-item>
			<a href="/">promotion management</a>
		</fb-breadcrumb-item>
		<fb-breadcrumb-item>promotion list</fb-breadcrumb-item>
		<fb-breadcrumb-item>promotion detail</fb-breadcrumb-item>
	</fb-breadcrumb>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbBreadcrumb, FbBreadcrumbItem },
        template: `
<div class="fb-breadcrumb-story-block">
	<div class="fb-breadcrumb-story-block__item">
		<fb-breadcrumb separator="/">
			<fb-breadcrumb-item :to="{ path: '/' }">homepage</fb-breadcrumb-item>
			<fb-breadcrumb-item>
				<a href="/">promotion management</a>
			</fb-breadcrumb-item>
			<fb-breadcrumb-item>promotion list</fb-breadcrumb-item>
			<fb-breadcrumb-item>promotion detail</fb-breadcrumb-item>
		</fb-breadcrumb>
	</div>
</div>`,
    }),
};

export const IconSeparator: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-breadcrumb :separator-icon="FasChevronRight">
		<fb-breadcrumb-item :to="{ path: '/' }">homepage</fb-breadcrumb-item>
		<fb-breadcrumb-item>promotion management</fb-breadcrumb-item>
		<fb-breadcrumb-item>promotion list</fb-breadcrumb-item>
		<fb-breadcrumb-item>promotion detail</fb-breadcrumb-item>
	</fb-breadcrumb>
</template>

<script setup lang="ts">
import { FasChevronRight } from '@fastybird/web-ui-icons';
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbBreadcrumb, FbBreadcrumbItem, FasChevronRight },
        setup: () => {
            return {
                FasChevronRight,
            };
        },
        template: `
<div class="fb-breadcrumb-story-block">
	<div class="fb-breadcrumb-story-block__item">
		<fb-breadcrumb :separator-icon="FasChevronRight">
			<fb-breadcrumb-item :to="{ path: '/' }">homepage</fb-breadcrumb-item>
			<fb-breadcrumb-item>promotion management</fb-breadcrumb-item>
			<fb-breadcrumb-item>promotion list</fb-breadcrumb-item>
			<fb-breadcrumb-item>promotion detail</fb-breadcrumb-item>
		</fb-breadcrumb>
	</div>
</div>`,
    }),
};
