import { reactive, ref } from "vue";

import { Meta, StoryObj } from "@storybook/vue3";
import { action } from "@storybook/addon-actions";
import { FasCircleXmark, FasImage, FasKeyboard } from "@fastybird/web-ui-icons";
import { FbModal, FbButton, FbIcon, FbForm, FbFormItem, FbInput } from "@fastybird/web-ui-components";
import { VariantTypes, LayoutTypes } from "@fastybird/web-ui-constants";

import "./fb-modal.stories.scss";

const meta: Meta<typeof FbModal> = {
    component: FbModal,
    title: "Components/Feedback/Modal",
    argTypes: {
        content: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "content of the modal footer for replacing whole modal content with custom one",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        header: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "content of the modal header for replacing whole modal header content with custom one",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        footer: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "content of the modal footer for replacing whole modal footer content with custom one",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        title: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "title of the modal",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        subtitle: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "subtitle of the modal",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        icon: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "modal header icon",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        default: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "content of modal",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        "left-button": {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "slot for using custom left button used as `Close` action",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        "right-button": {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "slot for using custom right button used as `Ok` action",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        variant: {
            type: { name: "string", required: false },
            control: { type: "select" },
            options: [VariantTypes.PRIMARY, VariantTypes.DEFAULT, VariantTypes.INFO, VariantTypes.SUCCESS, VariantTypes.WARNING, VariantTypes.ERROR],
            description: "modal header and button variant",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: VariantTypes.PRIMARY },
            },
        },
        layout: {
            type: { name: "string", required: false },
            control: { type: "select" },
            options: [LayoutTypes.DEFAULT, LayoutTypes.PHONE, LayoutTypes.TABLET],
            description: "modal layout",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: LayoutTypes.DEFAULT },
            },
        },
        width: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "width of modal, default is 50%",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "50%" },
            },
        },
        fullscreen: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether the modal takes up full screen",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        top: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "value for `margin-top` of modal CSS, default is 15vh",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "15vh" },
            },
        },
        showHeader: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether a modal header is displayed",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: true },
            },
        },
        showFooter: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether a modal footer is displayed",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: true },
            },
        },
        backdrop: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether a mask is displayed",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: true },
            },
        },
        customClass: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "custom class names for backdrop",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "-" },
            },
        },
        appendToBody: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether to append modal itself to body. A nested modal should have this attribute set to `true`",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        appendTo: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "which element the modal appends to. Will override `append-to-body`",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "body" },
            },
        },
        lockScroll: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether scroll of body is disabled while modal is displayed",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        openDelay: {
            type: { name: "number", required: false },
            control: { type: "text" },
            description: "the time in milliseconds before open",
            table: {
                type: { summary: "number" },
                defaultValue: { summary: 0 },
            },
        },
        closeDelay: {
            type: { name: "number", required: false },
            control: { type: "text" },
            description: "the time in milliseconds before close",
            table: {
                type: { summary: "number" },
                defaultValue: { summary: 0 },
            },
        },
        closeOnClickBackdrop: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether the modal can be closed by clicking the backdrop",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: true },
            },
        },
        closeOnPressEscape: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether the modal can be closed by pressing ESC",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: true },
            },
        },
        showClose: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether to show a close button",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: true },
            },
        },
        showLeftBtn: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether to show a left action button",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: true },
            },
        },
        leftBtnLabel: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "left action button label",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "Close" },
            },
        },
        showRightBtn: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether to show a right action button",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: true },
            },
        },
        rightBtnLabel: {
            type: { name: "string", required: false },
            control: { type: "text" },
            description: "right action button label",
            table: {
                type: { summary: "string" },
                defaultValue: { summary: "Ok" },
            },
        },
        draggable: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "enable dragging feature for modal",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        overflow: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "draggable modal can overflow the viewport",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        center: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether to align the header and footer in center",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        alignCenter: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether to align the modal both horizontally and vertically",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
        closable: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "whether enable or disable modal close action",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: true },
            },
        },
        destroyOnClose: {
            type: { name: "boolean", required: false },
            control: { type: "boolean" },
            description: "destroy elements in modal when closed",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: false },
            },
        },
    },
    args: {
        variant: VariantTypes.PRIMARY,
        layout: LayoutTypes.DEFAULT,
        width: "50%",
        fullscreen: false,
        top: "15vh",
        showHeader: true,
        showFooter: true,
        backdrop: true,
        appendToBody: false,
        appendTo: "body",
        lockScroll: false,
        openDelay: 0,
        closeDelay: 0,
        closeOnClickBackdrop: true,
        closeOnPressEscape: true,
        showClose: true,
        showLeftBtn: true,
        leftBtnLabel: "Close",
        showRightBtn: true,
        rightBtnLabel: "Ok",
        draggable: false,
        overflow: false,
        center: false,
        alignCenter: false,
        closable: true,
        destroyOnClose: false,
    },
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbModal>;

export const Playground: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="modalVisible = true">
		Click to open the Modal
	</fb-button>

	<fb-modal v-model="modalVisible">
		<span>This is a message</span>
	</fb-modal>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const modalVisible = ref(false);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: (args) => ({
        components: { FbModal, FbButton },
        setup() {
            const modalVisible = ref<boolean>(false);

            return { args, modalVisible };
        },
        methods: {
            onClick: action("button-clicked"),
            onClose: action("modal-close-action-triggered"),
            onLeftClick: action("modal-left-button-clicked"),
            onRightClick: action("modal-right-button-clicked"),
        },
        template: `
<fb-button @click.prevent="() => { modalVisible = true }">Open modal</fb-button>

<fb-modal
	v-model="modalVisible"
	v-bind="args"
	@close="(e) => { modalVisible = false; onClose(e) }"
	@leftClick="(e) => { modalVisible = false; onLeftClick(e) }"
	@rightClick="(e) => { modalVisible = false; onRightClick(e) }"
>
	<template
		v-if="${args.content !== null && typeof args.content !== "undefined"}"
		#content
	>
		${args.content}
	</template>

	<template
		v-if="${args.header !== null && typeof args.header !== "undefined"}"
		#header
	>
		${args.header}
	</template>

	<template
		v-if="${args.title !== null && typeof args.title !== "undefined"}"
		#title
	>
		${args.title}
	</template>

	<template
		v-if="${args.subtitle !== null && typeof args.subtitle !== "undefined"}"
		#subtitle
	>
		${args.subtitle}
	</template>

	<template
		v-if="${args.icon !== null && typeof args.icon !== "undefined"}"
		#icon
	>
		${args.icon}
	</template>

	<template
		v-if="${args.default !== null && typeof args.default !== "undefined"}"
		#default
	>
		${args.default}
	</template>

	<template
		v-if="${args.footer !== null && typeof args.footer !== "undefined"}"
		#footer
	>
		${args.footer}
	</template>

	<template
		v-if="${args["left-button"] !== null && typeof args["left-button"] !== "undefined"}"
		#left-button
	>
		${args["left-button"]}
	</template>

	<template
		v-if="${args["right-button"] !== null && typeof args["right-button"] !== "undefined"}"
		#right-button
	>
		${args["right-button"]}
	</template>
</fb-modal>`,
    }),
    args: {
        title: "Modal window header",
        default: `
			<div>
				<p>Phasellus sapien felis, vulputate a nibh eu, tempor dictum turpis. Pellentesque non ex condimentum, dictum mauris non, ullamcorper nisi. Nunc sodales vel libero ac gravida. Maecenas malesuada viverra odio at molestie.</p>
				<p>Donec ultrices vel nibh a iaculis. Morbi dapibus sollicitudin libero facilisis dapibus. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>
				<p>Phasellus sapien felis, vulputate a nibh eu, tempor dictum turpis. Pellentesque non ex condimentum, dictum mauris non, ullamcorper nisi. Nunc sodales vel libero ac gravida. Maecenas malesuada viverra odio at molestie.</p>
			</div>`,
    },
};

export const BasicUsage: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="modalVisible = true">
		Click to open the Modal
	</fb-button>

	<fb-modal
		v-model="modalVisible"
		title="Simple modal header"
		subtitle="With simple subtitle"
		width="500"
	>
		<span>Phasellus sapien felis, vulputate a nibh eu, tempor dictum turpis. Pellentesque non ex condimentum, dictum mauris non, ullamcorper nisi.</span>
		<template #icon><fas-image /></template>
	</fb-modal>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const modalVisible = ref(false);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbModal, FbButton, FasImage },
        setup() {
            const modalVisible = ref(false);

            return { FasImage, modalVisible };
        },
        template: `
<fb-button plain @click="modalVisible = true">
	Click to open the Modal
</fb-button>

<fb-modal
	v-model="modalVisible"
	title="Simple modal header"
	subtitle="With simple subtitle"
	width="500"
	@leftClick="(e) => { modalVisible = false; }"
	@rightClick="(e) => { modalVisible = false; }"
>
	<span>Phasellus sapien felis, vulputate a nibh eu, tempor dictum turpis. Pellentesque non ex condimentum, dictum mauris non, ullamcorper nisi.</span>
	<template #icon><fas-image /></template>
</fb-modal>`,
    }),
};

export const CustomContent: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="modalTableVisible = true">
		Open a Table nested Modal
	</fb-button>

	<fb-button plain @click="modalFormVisible = true">
		Open a Form nested Modal
	</fb-button>

	<fb-modal v-model="modalTableVisible" title="Shipping address" width="800">
		<fb-table :data="gridData">
			<fb-table-column property="date" label="Date" width="150" />
			<fb-table-column property="name" label="Name" width="200" />
			<fb-table-column property="address" label="Address" />
		</fb-table>
	</fb-modal>

	<fb-modal v-model="modalFormVisible" title="Shipping address" width="500">
		<fb-form :model="form">
			<fb-form-item label="Promotion name" :label-width="formLabelWidth">
				<fb-input v-model="form.name" autocomplete="off" />
			</fb-form-item>
			<fb-form-item label="Zones" :label-width="formLabelWidth">
				<fb-select v-model="form.region" placeholder="Please select a zone">
					<fb-option label="Zone No.1" value="shanghai" />
					<fb-option label="Zone No.2" value="beijing" />
				</fb-select>
			</fb-form-item>
		</fb-form>
		<template #footer>
			<div class="modal-footer">
				<fb-button @click="modalFormVisible = false">Cancel</fb-button>
				<fb-button variant="primary" @click="modalFormVisible = false">
					Confirm
				</fb-button>
			</div>
		</template>
	</fb-modal>
</template>

<script lang="ts" setup>
import { reactive, ref } from 'vue';

const modalTableVisible = ref(false);
const modalFormVisible = ref(false);
const formLabelWidth = '140px';

const form = reactive({
  name: '',
  region: '',
  date1: '',
  date2: '',
  delivery: false,
  type: [],
  resource: '',
  desc: '',
});

const gridData = [
  {
    date: '2016-05-02',
    name: 'John Smith',
    address: 'No.1518,  Jinshajiang Road, Putuo District',
  },
  {
    date: '2016-05-04',
    name: 'John Smith',
    address: 'No.1518,  Jinshajiang Road, Putuo District',
  },
  {
    date: '2016-05-01',
    name: 'John Smith',
    address: 'No.1518,  Jinshajiang Road, Putuo District',
  },
  {
    date: '2016-05-03',
    name: 'John Smith',
    address: 'No.1518,  Jinshajiang Road, Putuo District',
  },
];
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbModal, FbButton, FbForm, FbFormItem, FbInput, FasKeyboard },
        setup() {
            const modalTableVisible = ref(false);
            const modalFormVisible = ref(false);
            const formLabelWidth = "140px";

            const form = reactive({
                name: "",
                region: "",
                date1: "",
                date2: "",
                delivery: false,
                type: [],
                resource: "",
                desc: "",
            });

            const gridData = [
                {
                    date: "2016-05-02",
                    name: "John Smith",
                    address: "No.1518,  Jinshajiang Road, Putuo District",
                },
                {
                    date: "2016-05-04",
                    name: "John Smith",
                    address: "No.1518,  Jinshajiang Road, Putuo District",
                },
                {
                    date: "2016-05-01",
                    name: "John Smith",
                    address: "No.1518,  Jinshajiang Road, Putuo District",
                },
                {
                    date: "2016-05-03",
                    name: "John Smith",
                    address: "No.1518,  Jinshajiang Road, Putuo District",
                },
            ];

            return { modalTableVisible, modalFormVisible, formLabelWidth, form, gridData, FasKeyboard };
        },
        template: `
<fb-button plain @click="modalTableVisible = true">
	Open a Table nested Modal
</fb-button>

<fb-button plain @click="modalFormVisible = true">
	Open a Form nested Modal
</fb-button>

<fb-modal
	v-model="modalTableVisible"
	title="Shipping address"
	width="800"
	@leftClick="(e) => { modalTableVisible = false; }"
	@rightClick="(e) => { modalTableVisible = false; }"
>
	<fb-table :data="gridData">
		<fb-table-column property="date" label="Date" width="150" />
		<fb-table-column property="name" label="Name" width="200" />
		<fb-table-column property="address" label="Address" />
	</fb-table>
</fb-modal>

<fb-modal v-model="modalFormVisible" title="Shipping address" width="500">
	<template #icon>
		<fas-keyboard />
	</template>

	<fb-form :model="form">
		<fb-form-item label="Promotion name" :label-width="formLabelWidth">
			<fb-input v-model="form.name" autocomplete="off" />
		</fb-form-item>
		<fb-form-item label="Zones" :label-width="formLabelWidth">
			<fb-select v-model="form.region" placeholder="Please select a zone">
				<fb-option label="Zone No.1" value="shanghai" />
				<fb-option label="Zone No.2" value="beijing" />
			</fb-select>
		</fb-form-item>
	</fb-form>
	<template #footer>
		<div class="modal-footer">
			<fb-button @click="modalFormVisible = false">Cancel</fb-button>
			<fb-button variant="${VariantTypes.PRIMARY}" @click="modalFormVisible = false">
				Confirm
			</fb-button>
		</div>
	</template>
</fb-modal>`,
    }),
};

export const CustomHeader: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="modalVisible = true">
		Open Modal with customized header
	</fb-button>

	<fb-modal v-model="modalVisible" :show-close="false" width="500">
		<template #header="{ close, titleId, titleClass }">
			<div class="my-header">
				<h4 :id="titleId" :class="titleClass">This is a custom header!</h4>

				<fb-button variant="danger" @click="close">
					<fb-icon class="fb-icon--left"><fas-circle-xmark /></fb-icon>
					Close
				</fb-button>
			</div>
		</template>
		This is modal content.
	</fb-modal>
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import { FasCircleXmark } from '@fastybird/web-ui-icons';

const modalVisible = ref(false);
</script>

<style scoped>
.my-header {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  gap: 16px;
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbModal, FbButton, FbIcon, FasCircleXmark },
        setup() {
            const modalVisible = ref(false);

            return { FasCircleXmark, modalVisible };
        },
        template: `
<div class="fb-modal-story-block">
	<fb-button plain @click="modalVisible = true">
		Open Modal with customized header
	</fb-button>

	<fb-modal
		v-model="modalVisible"
		:show-close="false"
		width="500"
		@leftClick="(e) => { modalVisible = false; }"
		@rightClick="(e) => { modalVisible = false; }"
	>
		<template #header="{ close, titleId, titleClass }">
			<div class="fb-modal-story-block__custom-modal-header">
				<h4 :id="titleId" :class="titleClass">This is a custom header!</h4>

				<fb-button variant="${VariantTypes.DANGER}" @click="close">
					<fb-icon class="fb-icon--left"><fas-circle-xmark /></fb-icon>
					Close
				</fb-button>
			</div>
		</template>

		This is modal content.
	</fb-modal>
</div>`,
    }),
};

export const NestedModal: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="outerVisible = true">
		Open the outer Modal
	</fb-button>

	<fb-modal v-model="outerVisible" title="Outer Modal" width="800">
		<span>This is the outer Modal</span>
		<fb-modal
			v-model="innerVisible"
			width="500"
			title="Inner Modal"
			append-to-body
		>
			<span>This is the inner Modal</span>
		</fb-modal>
		<template #footer>
			<div class="modal-footer">
				<fb-button @click="outerVisible = false">Cancel</fb-button>
				<fb-button variant="primary" @click="innerVisible = true">
					Open the inner Modal
				</fb-button>
			</div>
		</template>
	</fb-modal>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const outerVisible = ref(false);
const innerVisible = ref(false);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbModal, FbButton },
        setup() {
            const outerVisible = ref(false);
            const innerVisible = ref(false);

            return { outerVisible, innerVisible };
        },
        template: `
<div>
	<fb-button plain @click="outerVisible = true">
		Open the outer Modal
	</fb-button>

	<fb-modal v-model="outerVisible" title="Outer Modal" width="800">
		<span>This is the outer Modal</span>
		<fb-modal
			v-model="innerVisible"
			width="500"
			title="Inner Modal"
			append-to-body
			@leftClick="(e) => { innerVisible = false; }"
			@rightClick="(e) => { innerVisible = false; }"
		>
			<span>This is the inner Modal</span>
		</fb-modal>
		<template #footer>
			<div class="modal-footer">
				<fb-button @click="outerVisible = false">Cancel</fb-button>
				<fb-button variant="${VariantTypes.PRIMARY}" @click="innerVisible = true">
					Open the inner Modal
				</fb-button>
			</div>
		</template>
	</fb-modal>
</div>`,
    }),
};

export const CentredContent: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="modalVisible = true">
		Click to open the Modal
	</fb-button>

	<fb-modal
		v-model="modalVisible"
		title="Simple modal header"
		subtitle="With simple subtitle"
		width="500"
		center
	>
		<span>It should be noted that the content will not be aligned in center by default</span>
	</fb-modal>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const modalVisible = ref(false);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbModal, FbButton, FasImage },
        setup() {
            const modalVisible = ref(false);

            return { FasImage, modalVisible };
        },
        template: `
<fb-button plain @click="modalVisible = true">
	Click to open the Modal
</fb-button>

<fb-modal
	v-model="modalVisible"
	title="Simple modal header"
	subtitle="With simple subtitle"
	width="500"
	center
	@leftClick="(e) => { modalVisible = false; }"
	@rightClick="(e) => { modalVisible = false; }"
>
	<span>It should be noted that the content will not be aligned in center by default</span>
</fb-modal>`,
    }),
};

export const AlignCentred: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="modalVisible = true">
		Click to open the Modal
	</fb-button>

	<fb-modal
		v-model="modalVisible"
		title="Simple modal header"
		subtitle="With simple subtitle"
		width="500"
		align-center
	>
		<span>It should be noted that the content will not be aligned in center by default</span>
	</fb-modal>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const modalVisible = ref(false);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbModal, FbButton, FasImage },
        setup() {
            const modalVisible = ref(false);

            return { FasImage, modalVisible };
        },
        template: `
<fb-button plain @click="modalVisible = true">
	Click to open the Modal
</fb-button>

<fb-modal
	v-model="modalVisible"
	title="Simple modal header"
	subtitle="With simple subtitle"
	width="500"
	align-center
	@leftClick="(e) => { modalVisible = false; }"
	@rightClick="(e) => { modalVisible = false; }"
>
	<span>It should be noted that the content will not be aligned in center by default</span>
</fb-modal>`,
    }),
};

export const Draggable: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="modalVisible = true">
    Open a draggable Modal
	</fb-button>

	<fb-button plain @click="modalOverflowVisible = true">
    Open a overflow draggable Modal
	</fb-button>

	<fb-modal
		v-model="modalVisible"
		title="Simple modal header"
		subtitle="With simple subtitle"
		width="500"
		draggable
	>
		<span>It's a draggable Dialog</span>
	</fb-modal>

	<fb-modal
		v-model="modalOverflowVisible"
		title="Simple modal header"
		subtitle="With simple subtitle"
		width="500"
		draggable
		overflow
	>
		<span>It's a overflow draggable Dialog</span>
	</fb-modal>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const modalVisible = ref(false);
const modalVisiblemodalOverflowVisible = ref(false);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbModal, FbButton, FasImage },
        setup() {
            const modalVisible = ref(false);
            const modalOverflowVisible = ref(false);

            return { FasImage, modalVisible, modalOverflowVisible };
        },
        template: `
<fb-button plain @click="modalVisible = true">
	Open a draggable Modal
</fb-button>

<fb-button plain @click="modalOverflowVisible = true">
	Open a overflow draggable Modal
</fb-button>

<fb-modal
	v-model="modalVisible"
	title="Simple modal header"
	subtitle="With simple subtitle"
	width="500"
	draggable
>
	<span>It's a draggable Dialog</span>
</fb-modal>

<fb-modal
	v-model="modalOverflowVisible"
	title="Simple modal header"
	subtitle="With simple subtitle"
	width="500"
	draggable
	overflow
>
	<span>It's a overflow draggable Dialog</span>
</fb-modal>`,
    }),
};

export const WithDescription: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="modalVisible = true">
		Click to open the Modal
	</fb-button>

	<fb-modal
		v-model="modalVisible"
		title="Simple modal header"
		subtitle="With simple subtitle"
		width="500"
	>
		<span>Phasellus sapien felis, vulputate a nibh eu, tempor dictum turpis. Pellentesque non ex condimentum, dictum mauris non, ullamcorper nisi.</span>
		<template #description>
			Phasellus sapien felis, vulputate a nibh eu, tempor dictum turpis. Pellentesque non ex condimentum, dictum mauris non, ullamcorper nisi.
		</template>
		<template #icon><fas-image /></template>
	</fb-modal>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const modalVisible = ref(false);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbModal, FbButton, FasImage },
        setup() {
            const modalVisible = ref(false);

            return { FasImage, modalVisible };
        },
        template: `
<fb-button plain @click="modalVisible = true">
	Click to open the Modal
</fb-button>

<fb-modal
	v-model="modalVisible"
	title="Simple modal header"
	subtitle="With simple subtitle"
	width="500"
	@leftClick="(e) => { modalVisible = false; }"
	@rightClick="(e) => { modalVisible = false; }"
>
	<span>Phasellus sapien felis, vulputate a nibh eu, tempor dictum turpis. Pellentesque non ex condimentum, dictum mauris non, ullamcorper nisi.</span>
	<template #description>
		Phasellus sapien felis, vulputate a nibh eu, tempor dictum turpis. Pellentesque non ex condimentum, dictum mauris non, ullamcorper nisi.
	</template>
	<template #icon><fas-image /></template>
</fb-modal>`,
    }),
};
