import { h, ref } from "vue";

import { FbButton, FbMessage, FbMessageBox, FbSwitch, MessageBoxActionTypes } from "@fastybird/web-ui-components";
import { VariantTypes } from "@fastybird/web-ui-constants";
import { Meta, StoryObj } from "@storybook/vue3";

import "./fb-message-box.stories.scss";

import type { MessageBoxAction } from "@fastybird/web-ui-components";

const meta: Meta<typeof FbMessageBox> = {
    title: "Components/Feedback/Message box",
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbMessageBox>;

export const Alert: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="openError">Click to open the error Alert message</fb-button>
	<fb-button plain @click="openInfo">Click to open the info Alert message</fb-button>
</template>

<script setup lang="ts">
import { FbMessage, FbMessageBox } from '@fastybird/web-ui-library'
import type { MessageBoxAction } from '@fastybird/web-ui-library'

const openError = () => {
	FbMessageBox.alert('This is a alert message', 'With some title', {
		confirmButtonText: 'This is OK',
		type: '${VariantTypes.ERROR}',
		onAction: (action: MessageBoxAction) => {
			FbMessage({
				type: '${VariantTypes.INFO}',
				message: \`You clicked: \${action}\`,
			});
		},
	});
};

const openInfo = () => {
	FbMessageBox.alert('This is a info message', 'With some title', {
		confirmButtonText: 'This is OK',
		type: '${VariantTypes.INFO}',
		onAction: (action: MessageBoxAction) => {
			FbMessage({
				type: '${VariantTypes.INFO}',
				message: \`You clicked: \${action}\`,
			});
		},
	});
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        methods: {
            openError: () => {
                FbMessageBox.alert("This is a alert message", "With some title", {
                    confirmButtonText: "This is OK",
                    type: VariantTypes.ERROR,
                    onAction: (action: MessageBoxAction) => {
                        FbMessage({
                            type: VariantTypes.INFO,
                            message: `You clicked: ${action}`,
                        });
                    },
                });
            },
            openInfo: () => {
                FbMessageBox.alert("This is a info message", "With some title", {
                    confirmButtonText: "This is OK",
                    type: VariantTypes.INFO,
                    onAction: (action: MessageBoxAction) => {
                        FbMessage({
                            type: VariantTypes.INFO,
                            message: `You clicked: ${action}`,
                        });
                    },
                });
            },
        },
        template: `
<fb-button plain @click="openError">Click to open the error Alert message</fb-button>
<fb-button plain @click="openInfo">Click to open the info Alert message</fb-button>
`,
    }),
};

export const Confirm: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="open">Click to open the Confirmation message</fb-button>
</template>

<script setup lang="ts">
import { FbMessage, FbMessageBox, MessageBoxActionTypes } from '@fastybird/web-ui-library'
import type { MessageBoxAction } from '@fastybird/web-ui-library'

const open = () => {
	FbMessageBox.confirm(
		'This action will permanently delete data. Continue?',
		'Are you sure?',
		{
			confirmButtonText: 'OK',
			cancelButtonText: 'Cancel',
			type: '${VariantTypes.WARNING}',
			onAction: (action: MessageBoxAction): void => {
				if (action === MessageBoxActionTypes.CONFIRM) {
					FbMessage({
						type: '${VariantTypes.SUCCESS}',
						message: 'Delete completed',
					});
				} else {
					FbMessage({
						type: '${VariantTypes.INFO}',
						message: 'Delete canceled',
					});
				}
			},
		}
	);
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        methods: {
            open: () => {
                FbMessageBox.confirm("This action will permanently delete data. Continue?", "Are you sure?", {
                    confirmButtonText: "OK",
                    cancelButtonText: "Cancel",
                    type: VariantTypes.WARNING,
                    onAction: (action: MessageBoxAction): void => {
                        if (action === MessageBoxActionTypes.CONFIRM) {
                            FbMessage({
                                type: VariantTypes.SUCCESS,
                                message: "Delete completed",
                            });
                        } else {
                            FbMessage({
                                type: VariantTypes.INFO,
                                message: "Delete canceled",
                            });
                        }
                    },
                });
            },
        },
        template: '<fb-button plain @click="open">Click to open the Confirmation message</fb-button>',
    }),
};

export const Prompt: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="open">Click to open the Confirmation message</fb-button>
</template>

<script setup lang="ts">
import { FbMessage, FbMessageBox, MessageBoxActionTypes } from '@fastybird/web-ui-library'
import type { MessageBoxAction } from '@fastybird/web-ui-library'

const open = () => {
	FbMessageBox.prompt('Please input your e-mail', 'Tip', {
		confirmButtonText: 'OK',
		cancelButtonText: 'Cancel',
		inputPattern: /[\\w!#$%&'*+/=?^_\`{|}~-]+(?:\\.[\\w!#$%&'*+/=?^_\`{|}~-]+)*@(?:[\\w](?:[\\w-]*[\\w])?.)+[\\w](?:[\\w-]*[\\w])?/,
		inputErrorMessage: 'Invalid Email',
		onAction: (action: MessageBoxAction, _context, value: string): void => {
			if (action === MessageBoxActionTypes.CONFIRM) {
				FbMessage({
					type: '${VariantTypes.SUCCESS}',
					message: \`Your email is: \${value}\`,
				});
			} else {
				FbMessage({
					type: '${VariantTypes.INFO}',
					message: 'Input canceled',
				});
			}
		},
	});
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        methods: {
            open: () => {
                FbMessageBox.prompt("Please input your e-mail", "Tip", {
                    confirmButtonText: "OK",
                    cancelButtonText: "Cancel",
                    inputPattern: /[\w!#$%&'*+/=?^_`{|}~-]+(?:\.[\w!#$%&'*+/=?^_`{|}~-]+)*@(?:[\w](?:[\w-]*[\w])?\.)+[\w](?:[\w-]*[\w])?/,
                    inputErrorMessage: "Invalid Email",
                    onAction: (action: MessageBoxAction, _context, value: string): void => {
                        if (action === MessageBoxActionTypes.CONFIRM) {
                            FbMessage({
                                type: VariantTypes.SUCCESS,
                                message: `Your email is: ${value}`,
                            });
                        } else {
                            FbMessage({
                                type: VariantTypes.INFO,
                                message: "Input canceled",
                            });
                        }
                    },
                });
            },
        },
        template: '<fb-button plain @click="open">Click to open the Prompt message</fb-button>',
    }),
};

export const Vnode: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="openOne">Common VNode</fb-button>
	<fb-button plain @click="openTwo">Dynamic props</fb-button>
</template>

<script setup lang="ts">
import { h, ref } from 'vue';
import { FbMessage, FbMessageBox, MessageBoxActionTypes, FbSwitch } from '@fastybird/web-ui-library'
import type { MessageBoxAction } from '@fastybird/web-ui-library'

const checked = ref<boolean | string | number>(false);

const openOne = () => {
	FbMessageBox({
		title: 'Message',
		message: h('p', null, [h('span', null, 'Message can be '), h('i', { style: 'color: teal' }, 'VNode')]),
	});
}
const openTwo = () => {
	FbMessageBox({
		title: 'Message',
		// Should pass a function if VNode contains dynamic props
		message: () =>
			h(FbSwitch, {
				modelValue: checked.value,
				'onUpdate:modelValue': (val: boolean | string | number) => {
					checked.value = val;
				},
			}),
	});
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton, FbSwitch },
        methods: {
            openOne: () => {
                FbMessageBox({
                    title: "Message",
                    message: h("p", null, [h("span", null, "Message can be "), h("i", { style: "color: teal" }, "VNode")]),
                });
            },
            openTwo: () => {
                const checked = ref<boolean | string | number>(false);

                FbMessageBox({
                    title: "Message",
                    // Should pass a function if VNode contains dynamic props
                    message: () =>
                        h(FbSwitch, {
                            modelValue: checked.value,
                            "onUpdate:modelValue": (val: boolean | string | number) => {
                                checked.value = val;
                            },
                        }),
                });
            },
        },
        template: `
<fb-button plain @click="openOne">Common VNode</fb-button>
<fb-button plain @click="openTwo">Dynamic props</fb-button>
`,
    }),
};

export const Customization: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-button plain @click="open">Click to open the Confirmation message</fb-button>
</template>

<script setup lang="ts">
import { FbMessage, FbMessageBox, MessageBoxActionTypes } from '@fastybird/web-ui-library'

const open = () => {
	FbMessageBox.confirm(
		'This action will permanently delete data. Continue?',
		'Are you sure?',
		{
			confirmButtonText: 'OK',
			cancelButtonText: 'Cancel',
			type: '${VariantTypes.WARNING}',
			onAction: (action): void => {
				if (action === MessageBoxActionTypes.CONFIRM) {
					FbMessage({
						type: '${VariantTypes.SUCCESS}',
						message: 'Delete completed',
					});
				} else {
					FbMessage({
						type: '${VariantTypes.INFO}',
						message: 'Delete canceled',
					});
				}
			},
		}
	);
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbButton },
        methods: {
            open: () => {
                FbMessageBox({
                    title: "Message",
                    message: h("p", null, [h("span", null, "Message can be "), h("i", { style: "color: teal" }, "VNode")]),
                    showCancelButton: true,
                    confirmButtonText: "OK",
                    cancelButtonText: "Cancel",
                    beforeClose: (action: MessageBoxAction, instance, done) => {
                        if (action === MessageBoxActionTypes.CONFIRM) {
                            instance.confirmButtonRef.value.loading = true;
                            instance.confirmButtonRef.value.text = "Loading...";

                            setTimeout(() => {
                                done();
                                setTimeout(() => {
                                    instance.confirmButtonRef.value.loading = false;
                                }, 300);
                            }, 3000);
                        } else {
                            done();
                        }
                    },
                    onAction: (action: MessageBoxAction): void => {
                        if (action === MessageBoxActionTypes.CONFIRM) {
                            FbMessage({
                                type: VariantTypes.SUCCESS,
                                message: "Delete completed",
                            });
                        } else {
                            FbMessage({
                                type: VariantTypes.INFO,
                                message: "Delete canceled",
                            });
                        }
                    },
                });
            },
        },
        template: '<fb-button plain @click="open">Click to open the Customized message</fb-button>',
    }),
};
