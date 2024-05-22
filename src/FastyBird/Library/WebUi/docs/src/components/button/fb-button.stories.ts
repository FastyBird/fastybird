import { Meta, StoryObj } from "@storybook/vue3";
import { action } from "@storybook/addon-actions";
import { fn } from "@storybook/test";
import {
    FasMagnifyingGlass,
    FasPenToSquare,
    FasEnvelope,
    FasCheck,
    FasStar,
    FasTrashCan,
    FasShareNodes,
    FasSpinner,
    FasUpload,
} from "@fastybird/web-ui-icons";
import { FbButton, ButtonButtonTypes, FbIcon } from "@fastybird/web-ui-components";
import { ComponentSizeTypes, VariantTypes } from "@fastybird/web-ui-constants";

import "./fb-button.stories.scss";

const meta: Meta<typeof FbButton> = {
    component: FbButton,
    title: "Components/Basic/Button",
    argTypes: {
        default: {
            type: { name: "string", required: true },
            control: { type: "text" },
            description: "required slot representing button content",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        size: {
            type: { name: "string", required: false },
            control: { type: "select" },
            options: [ComponentSizeTypes.LARGE, ComponentSizeTypes.DEFAULT, ComponentSizeTypes.SMALL],
            description: "button size",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: ComponentSizeTypes.DEFAULT },
            },
        },
        variant: {
            type: { name: "string", required: false },
            control: { type: "select" },
            options: [
                "",
                VariantTypes.DEFAULT,
                VariantTypes.PRIMARY,
                VariantTypes.INFO,
                VariantTypes.SUCCESS,
                VariantTypes.WARNING,
                VariantTypes.DANGER,
            ],
            description: "button variant",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        type: {
            type: { name: "string", required: false },
            control: { type: "select" },
            options: [ButtonButtonTypes.BUTTON, ButtonButtonTypes.SUBMIT, ButtonButtonTypes.RESET],
            description: "same as native button's `type`",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: ButtonButtonTypes.BUTTON },
            },
        },
        block: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether button is stretched to full width",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        uppercase: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether button text is in uppercase",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        link: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether it's a link button",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        round: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether it's a round button",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        circle: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether it's a circle button",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        text: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether it's a text button",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        plain: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether it's a plain button",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        bg: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether the text button background color is always on",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        color: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "custom button color, automatically calculate `hover` and `active` color",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        active: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether it's a active button",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        disabled: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether button is disabled",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        autofocus: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "same as native button's `autofocus`",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        loading: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether it's loading",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        loadingIcon: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "customize loading icon component",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        icon: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "icon component",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        tag: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "custom element tag",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "button" },
            },
        },
        dark: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "dark mode, which automatically converts `color` to dark mode colors",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        onClick: {
            table: {
                disable: true,
            },
        },
    },
    args: {
        size: ComponentSizeTypes.DEFAULT,
        type: ButtonButtonTypes.BUTTON,
        block: false,
        uppercase: false,
        link: false,
        round: false,
        circle: false,
        text: false,
        plain: false,
        bg: false,
        active: false,
        disabled: false,
        autofocus: false,
        loading: false,
        tag: "button",
        dark: false,
        onClick: fn(),
    },
    excludeStories: /.*Data$/,
};

export const actionsData = {
    onClick: action("click"),
};

export default meta;

type Story = StoryObj<typeof FbButton>;

export const Component: Story = {
    tags: ["hideInSidebar"],
    args: {
        default: "Default",
    },
};

export const BasicUsage: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<div>
		<fb-button>Default</fb-button>
		<fb-button variant="${VariantTypes.PRIMARY}">Primary</fb-button>
		<fb-button variant="${VariantTypes.SUCCESS}">Success</fb-button>
		<fb-button variant="${VariantTypes.INFO}">Info</fb-button>
		<fb-button variant="${VariantTypes.WARNING}">Warning</fb-button>
		<fb-button variant="${VariantTypes.DANGER}">Danger</fb-button>
	</div>
	
	<div>
		<fb-button plain>Plain</fb-button>
		<fb-button variant="${VariantTypes.PRIMARY}" plain>Primary</fb-button>
		<fb-button variant="${VariantTypes.SUCCESS}" plain>Success</fb-button>
		<fb-button variant="${VariantTypes.INFO}" plain>Info</fb-button>
		<fb-button variant="${VariantTypes.WARNING}" plain>Warning</fb-button>
		<fb-button variant="${VariantTypes.DANGER}" plain>Danger</fb-button>
	</div>
	
	<div>
		<fb-button round>Round</fb-button>
		<fb-button variant="${VariantTypes.PRIMARY}" round>Primary</fb-button>
		<fb-button variant="${VariantTypes.SUCCESS}" round>Success</fb-button>
		<fb-button variant="${VariantTypes.INFO}" round>Info</fb-button>
		<fb-button variant="${VariantTypes.WARNING}" round>Warning</fb-button>
		<fb-button variant="${VariantTypes.DANGER}" round>Danger</fb-button>
	</div>
	
	<div>
		<fb-button :icon="FasMagnifyingGlass" circle />
		<fb-button variant="${VariantTypes.PRIMARY}" :icon="FasPenToSquare" circle />
		<fb-button variant="${VariantTypes.SUCCESS}" :icon="FasEnvelope" circle />
		<fb-button variant="${VariantTypes.INFO}" :icon="FasCheck" circle />
		<fb-button variant="${VariantTypes.WARNING}" :icon="FasStar" circle />
		<fb-button variant="${VariantTypes.DANGER}" :icon="FasTrashCan" circle />
	</div>
</template>

<script setup lang="ts">
import {
  FasMagnifyingGlass,
  FasCheck,
  FasEnvelope,
  FasPenToSquare,
  FasStar,
  FasTrashCan,
} from '@fastybird/web-ui-icons';
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        setup() {
            return { FasMagnifyingGlass, FasPenToSquare, FasEnvelope, FasCheck, FasStar, FasTrashCan };
        },
        template: `
<div class="fb-button-story-block">
	<div class="fb-button-story-block__item">
		<fb-button>Default</fb-button>
		<fb-button variant="${VariantTypes.PRIMARY}">Primary</fb-button>
		<fb-button variant="${VariantTypes.INFO}">Info</fb-button>
		<fb-button variant="${VariantTypes.SUCCESS}">Success</fb-button>
		<fb-button variant="${VariantTypes.WARNING}">Warning</fb-button>
		<fb-button variant="${VariantTypes.DANGER}">Danger</fb-button>
	</div>
	<div class="fb-button-story-block__item">
		<fb-button plain>Plain</fb-button>
		<fb-button variant="${VariantTypes.PRIMARY}" plain>Primary</fb-button>
		<fb-button variant="${VariantTypes.INFO}" plain>Info</fb-button>
		<fb-button variant="${VariantTypes.SUCCESS}" plain>Success</fb-button>
		<fb-button variant="${VariantTypes.WARNING}" plain>Warning</fb-button>
		<fb-button variant="${VariantTypes.DANGER}" plain>Danger</fb-button>
	</div>
	<div class="fb-button-story-block__item">
		<fb-button round>Round</fb-button>
		<fb-button variant="${VariantTypes.PRIMARY}" round>Primary</fb-button>
		<fb-button variant="${VariantTypes.INFO}" round>Info</fb-button>
		<fb-button variant="${VariantTypes.SUCCESS}" round>Success</fb-button>
		<fb-button variant="${VariantTypes.WARNING}" round>Warning</fb-button>
		<fb-button variant="${VariantTypes.DANGER}" round>Danger</fb-button>
	</div>
	<div class="fb-button-story-block__item">
		<fb-button circle :icon="FasMagnifyingGlass" />
		<fb-button variant="${VariantTypes.PRIMARY}" circle :icon="FasPenToSquare" />
		<fb-button variant="${VariantTypes.SUCCESS}" circle :icon="FasEnvelope" />
		<fb-button variant="${VariantTypes.INFO}" circle :icon="FasCheck" />
		<fb-button variant="${VariantTypes.WARNING}" circle :icon="FasStar" />
		<fb-button variant="${VariantTypes.DANGER}" circle :icon="FasTrashCan" />
	</div>
</div>`,
    }),
};

export const Disabled: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<div>
		<fb-button disabled>Default</fb-button>
		<fb-button variant="${VariantTypes.PRIMARY}" disabled>Primary</fb-button>
		<fb-button variant="${VariantTypes.INFO}" disabled>Info</fb-button>
		<fb-button variant="${VariantTypes.SUCCESS}" disabled>Success</fb-button>
		<fb-button variant="${VariantTypes.WARNING}" disabled>Warning</fb-button>
		<fb-button variant="${VariantTypes.DANGER}" disabled>Danger</fb-button>
	</div>

	<div>
		<fb-button plain disabled>Plain</fb-button>
		<fb-button variant="${VariantTypes.PRIMARY}" plain disabled>Primary</fb-button>
		<fb-button variant="${VariantTypes.INFO}" plain disabled>Info</fb-button>
		<fb-button variant="${VariantTypes.SUCCESS}" plain disabled>Success</fb-button>
		<fb-button variant="${VariantTypes.WARNING}" plain disabled>Warning</fb-button>
		<fb-button variant="${VariantTypes.DANGER}" plain disabled>Danger</fb-button>
	</div>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        template: `
<div class="fb-button-story-block">
	<div class="fb-button-story-block__item">
		<fb-button disabled>Default</fb-button>
		<fb-button variant="${VariantTypes.PRIMARY}" disabled>Primary</fb-button>
		<fb-button variant="${VariantTypes.INFO}" disabled>Info</fb-button>
		<fb-button variant="${VariantTypes.SUCCESS}" disabled>Success</fb-button>
		<fb-button variant="${VariantTypes.WARNING}" disabled>Warning</fb-button>
		<fb-button variant="${VariantTypes.DANGER}" disabled>Danger</fb-button>
	</div>
	<div class="fb-button-story-block__item">
		<fb-button plain disabled>Plain</fb-button>
		<fb-button variant="${VariantTypes.PRIMARY}" plain disabled>Primary</fb-button>
		<fb-button variant="${VariantTypes.INFO}" plain disabled>Info</fb-button>
		<fb-button variant="${VariantTypes.SUCCESS}" plain disabled>Success</fb-button>
		<fb-button variant="${VariantTypes.WARNING}" plain disabled>Warning</fb-button>
		<fb-button variant="${VariantTypes.DANGER}" plain disabled>Danger</fb-button>
	</div>
</div>`,
    }),
};

export const Link: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button link>Default</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}" link>Primary</fb-button>
	<fb-button variant="${VariantTypes.INFO}" link>Info</fb-button>
	<fb-button variant="${VariantTypes.SUCCESS}" link>Success</fb-button>
	<fb-button variant="${VariantTypes.WARNING}" link>Warning</fb-button>
	<fb-button variant="${VariantTypes.DANGER}" link>Danger</fb-button>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        template: `
<div class="fb-button-story-block">
	<fb-button link>Default</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}" link>Primary</fb-button>
	<fb-button variant="${VariantTypes.INFO}" link>Info</fb-button>
	<fb-button variant="${VariantTypes.SUCCESS}" link>Success</fb-button>
	<fb-button variant="${VariantTypes.WARNING}" link>Warning</fb-button>
	<fb-button variant="${VariantTypes.DANGER}" link>Danger</fb-button>
</div>`,
    }),
};

export const LinkDisabled: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button link disabled>Plain</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}" link disabled>Primary</fb-button>
	<fb-button variant="${VariantTypes.INFO}" link disabled>Info</fb-button>
	<fb-button variant="${VariantTypes.SUCCESS}" link disabled>Success</fb-button>
	<fb-button variant="${VariantTypes.WARNING}" link disabled>Warning</fb-button>
	<fb-button variant="${VariantTypes.DANGER}" link disabled>Danger</fb-button>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        template: `
<div class="fb-button-story-block">
	<fb-button link disabled>Plain</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}" link disabled>Primary</fb-button>
	<fb-button variant="${VariantTypes.INFO}" link disabled>Info</fb-button>
	<fb-button variant="${VariantTypes.SUCCESS}" link disabled>Success</fb-button>
	<fb-button variant="${VariantTypes.WARNING}" link disabled>Warning</fb-button>
	<fb-button variant="${VariantTypes.DANGER}" link disabled>Danger</fb-button>
</div>`,
    }),
};

export const Text: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button text>Plain</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}" text>Primary</fb-button>
	<fb-button variant="${VariantTypes.INFO}" text>Info</fb-button>
	<fb-button variant="${VariantTypes.SUCCESS}" text>Success</fb-button>
	<fb-button variant="${VariantTypes.WARNING}" text>Warning</fb-button>
	<fb-button variant="${VariantTypes.DANGER}" text>Danger</fb-button>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        template: `
<div class="fb-button-story-block">
	<fb-button text>Plain</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}" text>Primary</fb-button>
	<fb-button variant="${VariantTypes.INFO}" text>Info</fb-button>
	<fb-button variant="${VariantTypes.SUCCESS}" text>Success</fb-button>
	<fb-button variant="${VariantTypes.WARNING}" text>Warning</fb-button>
	<fb-button variant="${VariantTypes.DANGER}" text>Danger</fb-button>
</div>`,
    }),
};

export const TextBg: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button text bg>Plain</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}" text bg>Primary</fb-button>
	<fb-button variant="${VariantTypes.INFO}" text bg>Info</fb-button>
	<fb-button variant="${VariantTypes.SUCCESS}" text bg>Success</fb-button>
	<fb-button variant="${VariantTypes.WARNING}" text bg>Warning</fb-button>
	<fb-button variant="${VariantTypes.DANGER}" text bg>Danger</fb-button>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        template: `
<div class="fb-button-story-block">
	<fb-button text bg>Plain</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}" text bg>Primary</fb-button>
	<fb-button variant="${VariantTypes.INFO}" text bg>Info</fb-button>
	<fb-button variant="${VariantTypes.SUCCESS}" text bg>Success</fb-button>
	<fb-button variant="${VariantTypes.WARNING}" text bg>Warning</fb-button>
	<fb-button variant="${VariantTypes.DANGER}" text bg>Danger</fb-button>
</div>`,
    }),
};

export const TextDisabled: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button text disabled>Plain</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}" text disabled>Primary</fb-button>
	<fb-button variant="${VariantTypes.INFO}" text disabled>Info</fb-button>
	<fb-button variant="${VariantTypes.SUCCESS}" text disabled>Success</fb-button>
	<fb-button variant="${VariantTypes.WARNING}" text disabled>Warning</fb-button>
	<fb-button variant="${VariantTypes.DANGER}" text disabled>Danger</fb-button>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        template: `
<div class="fb-button-story-block">
	<fb-button text disabled>Plain</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}" text disabled>Primary</fb-button>
	<fb-button variant="${VariantTypes.INFO}" text disabled>Info</fb-button>
	<fb-button variant="${VariantTypes.SUCCESS}" text disabled>Success</fb-button>
	<fb-button variant="${VariantTypes.WARNING}" text disabled>Warning</fb-button>
	<fb-button variant="${VariantTypes.DANGER}" text disabled>Danger</fb-button>
</div>`,
    }),
};

export const Icon: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button variant="${VariantTypes.PRIMARY}" :icon="FasPenToSquare" />
	<fb-button variant="${VariantTypes.PRIMARY}" :icon="FasShareNodes" />
	<fb-button variant="${VariantTypes.PRIMARY}" :icon="FasTrashCan" />
	<fb-button variant="${VariantTypes.PRIMARY}" :icon="FasMagnifyingGlass">Search</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}">
		Upload
		<fb-icon class="button-icon">
			<fas-upload />
		</fb-icon>
	</fb-button>
</template>

<script setup lang="ts">
import {
  FasPenToSquare,
  FasShareNodes,
  FasTrashCan,
  FasMagnifyingGlass,
  FasUpload,
 } from '@fastybird/web-ui-icons';
</script>

<style scoped>
.button-icon {
	margin-left: 6px;
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton, FbIcon, FasUpload },
        setup() {
            return { FasPenToSquare, FasShareNodes, FasTrashCan, FasMagnifyingGlass };
        },
        template: `
<div class="fb-button-story-block">
	<fb-button variant="${VariantTypes.PRIMARY}" :icon="FasPenToSquare" />
	<fb-button variant="${VariantTypes.PRIMARY}" :icon="FasShareNodes" />
	<fb-button variant="${VariantTypes.PRIMARY}" :icon="FasTrashCan" />
	<fb-button variant="${VariantTypes.PRIMARY}" :icon="FasMagnifyingGlass">Search</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}">Upload<fb-icon class="button-icon"><fas-upload /></fb-icon></fb-button>
</div>`,
    }),
};

export const Loading: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button variant="${VariantTypes.PRIMARY}" loading>Loading</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}" loading :loadingIcon="FasSpinner">Loading</fb-button>
	<fb-button variant='${VariantTypes.PRIMARY}' loading>
		<template #loading>
			<fb-icon :size="18">
				<svg viewBox="-10, -10, 50, 50" class="custom-loading">
					<path
						d="
							M 30 15
							L 28 17
							M 25.61 25.61
							A 15 15, 0, 0, 1, 15 30
							A 15 15, 0, 1, 1, 27.99 7.5
							L 15 15
						"
					/>
				</svg>
			</fb-icon>
		</template>
		Loading
	</fb-button>
</template>

<style scoped>
.custom-loading {
	animation: loading-rotate 2s linear infinite;
}
.custom-loading path {
	fill: rgba(0, 0, 0, 0);
	animation: loading-dash 1.5s ease-in-out infinite;
	stroke-dasharray: 90, 150;
	stroke-dashoffset: 0;
	stroke-width: 2px;
	stroke: var(--fb-button-text-color);
	stroke-linecap: round;
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton, FbIcon },
        setup() {
            return { FasSpinner };
        },
        template: `
<div class="fb-button-story-block">
	<fb-button variant="${VariantTypes.PRIMARY}" loading>Loading</fb-button>
	<fb-button variant="${VariantTypes.PRIMARY}" loading :loadingIcon="FasSpinner">Loading</fb-button>
	<fb-button variant='${VariantTypes.PRIMARY}' loading>
		<template #loading>
			<fb-icon :size="18">
				<svg viewBox="-10, -10, 50, 50" class="custom-loading">
					<path
						d="
							M 30 15
							L 28 17
							M 25.61 25.61
							A 15 15, 0, 0, 1, 15 30
							A 15 15, 0, 1, 1, 27.99 7.5
							L 15 15
						"
					/>
				</svg>
			</fb-icon>
		</template>
		Loading
	</fb-button>
</div>`,
    }),
};

export const Sizes: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<div>
		<fb-button size="${ComponentSizeTypes.LARGE}">Large</fb-button>
		<fb-button>Default</fb-button>
		<fb-button size="${ComponentSizeTypes.SMALL}">Small</fb-button>

		<fb-button size="${ComponentSizeTypes.LARGE}" :icon="FasMagnifyingGlass">Large</fb-button>
		<fb-button :icon="FasMagnifyingGlass">Default</fb-button>
		<fb-button size="${ComponentSizeTypes.SMALL}" :icon="FasMagnifyingGlass">Small</fb-button>
	</div>
	<div>
		<fb-button size="${ComponentSizeTypes.LARGE}" round>Large</fb-button>
		<fb-button round>Default</fb-button>
		<fb-button size="${ComponentSizeTypes.SMALL}" round>Small</fb-button>

		<fb-button size="${ComponentSizeTypes.LARGE}" round :icon="FasMagnifyingGlass">Large</fb-button>
		<fb-button round :icon="FasMagnifyingGlass">Default</fb-button>
		<fb-button size="${ComponentSizeTypes.SMALL}" round :icon="FasMagnifyingGlass">Small</fb-button>
	</div>
	<div>
		<fb-button size="${ComponentSizeTypes.LARGE}" circle :icon="FasMagnifyingGlass" />
		<fb-button circle :icon="FasMagnifyingGlass" />
		<fb-button size="${ComponentSizeTypes.SMALL}" circle :icon="FasMagnifyingGlass" />
	</div>
</template>

<script setup lang="ts">
import { FasMagnifyingGlass } from '@fastybird/web-ui-icons';
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        setup() {
            return { FasMagnifyingGlass };
        },
        template: `
<div class="fb-button-story-block">
	<div class="fb-button-story-block__item">
		<fb-button size="${ComponentSizeTypes.LARGE}">Large</fb-button>
		<fb-button>Default</fb-button>
		<fb-button size="${ComponentSizeTypes.SMALL}">Small</fb-button>

		<fb-button size="${ComponentSizeTypes.LARGE}" :icon="FasMagnifyingGlass">Large</fb-button>
		<fb-button :icon="FasMagnifyingGlass">Default</fb-button>
		<fb-button size="${ComponentSizeTypes.SMALL}" :icon="FasMagnifyingGlass">Small</fb-button>
	</div>
	<div class="fb-button-story-block__item">
		<fb-button size="${ComponentSizeTypes.LARGE}" round>Large</fb-button>
		<fb-button round>Default</fb-button>
		<fb-button size="${ComponentSizeTypes.SMALL}" round>Small</fb-button>

		<fb-button size="${ComponentSizeTypes.LARGE}" round :icon="FasMagnifyingGlass">Large</fb-button>
		<fb-button round :icon="FasMagnifyingGlass">Default</fb-button>
		<fb-button size="${ComponentSizeTypes.SMALL}" round :icon="FasMagnifyingGlass">Small</fb-button>
	</div>
	<div class="fb-button-story-block__item">
		<fb-button size="${ComponentSizeTypes.LARGE}" circle :icon="FasMagnifyingGlass" />
		<fb-button circle :icon="FasMagnifyingGlass" />
		<fb-button size="${ComponentSizeTypes.SMALL}" circle :icon="FasMagnifyingGlass" />
	</div>
</div>`,
    }),
};

export const Tag: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button>button</fb-button>
	<fb-button tag="div" role="button" tabindex="0">div</fb-button>
	<fb-button
		variant="${VariantTypes.PRIMARY}"
		tag="a"
		href="https://github.com/fastybird/fastybird"
		target="_blank"
		rel="noopener noreferrer"
	>
		a
	</fb-button>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        template: `
<div class="fb-button-story-block">
	<fb-button>button</fb-button>
	<fb-button tag="div" role="button" tabindex="0">div</fb-button>
	<fb-button
		variant="${VariantTypes.PRIMARY}"
		tag="a"
		href="https://github.com/fastybird/fastybird"
		target="_blank"
		rel="noopener noreferrer"
	>
		a
	</fb-button>
</div>`,
    }),
};

export const CustomColor: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button color="#626aef">Default</fb-button>
	<fb-button color="#626aef" plain>Plain</fb-button>

	<fb-button color="#626aef" disabled>Disabled</fb-button>
	<fb-button color="#626aef" disabled plain>Disabled Plain</fb-button>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        template: `
<div class="fb-button-story-block">
	<fb-button color="#626aef">Default</fb-button>
	<fb-button color="#626aef" plain>Plain</fb-button>

	<fb-button color="#626aef" disabled>Disabled</fb-button>
	<fb-button color="#626aef" disabled plain>Disabled Plain</fb-button>
</div>`,
    }),
};
