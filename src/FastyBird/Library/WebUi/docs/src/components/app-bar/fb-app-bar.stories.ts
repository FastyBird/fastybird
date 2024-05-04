import { Meta, StoryObj } from "@storybook/vue3";
import { FasMagnifyingGlass, FasPenToSquare } from "@fastybird/web-ui-icons";
import { FbAppBar, FbAppBarHeading, FbAppBarButton, FbAppBarIcon, FbAppBarContent, FbButton, FbIcon } from "@fastybird/web-ui-components";

import "./fb-app-bar.stories.scss";
import { ref } from "vue";

const meta: Meta<typeof FbAppBar> = {
    component: FbAppBar,
    title: "Components/Navigation/App bar",
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbAppBar>;

export const BasicUsage: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
</template>

<script lang="ts" setup>
</script>

<style scoped>
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: {
            FbAppBar,
            FbAppBarHeading,
            FbAppBarButton,
            FbAppBarIcon,
            FbAppBarContent,
            FbButton,
            FbIcon,
            FasMagnifyingGlass,
            FasPenToSquare,
        },
        setup: () => {
            const menuCollapsed = ref<boolean>(true);

            return {
                menuCollapsed,
                FasMagnifyingGlass,
                FasPenToSquare,
            };
        },
        template: `
<div class="fb-app-bar-story-block">
	<fb-app-bar
		:menuCollapsed="menuCollapsed"
		@toggleMenu="menuCollapsed = !menuCollapsed"
	>
		<template #heading>
			<fb-app-bar-heading>
				<template #title>
					Simple header content
				</template>

				<template #subtitle>
					With short sub-header
				</template>

				<template #prepend>
					<fb-icon size="25px">
						<fas-pen-to-square />
					</fb-icon>
				</template>
			</fb-app-bar-heading>
		</template>

		<template #button-small>
			<fb-app-bar-button small>
				Close
			</fb-app-bar-button>

			<fb-app-bar-button small right>
				Next step
			</fb-app-bar-button>
		</template>

		<template #button-left>
			<fb-app-bar-button>
				<template #icon>
					<fb-icon>
						<fas-magnifying-glass />
					</fb-icon>
				</template>
			</fb-app-bar-button>
		</template>

		<template #content>
			<fb-app-bar-content>Bottom custom content</fb-app-bar-content>
		</template>
	</fb-app-bar>
</div>`,
    }),
};
