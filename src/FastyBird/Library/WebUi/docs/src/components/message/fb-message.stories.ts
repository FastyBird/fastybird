import { h } from 'vue';

import { Meta, StoryObj } from '@storybook/vue3';
import { FbMessage, FbButton } from '@fastybird/web-ui-components';
import { VariantTypes } from '@fastybird/web-ui-constants';

import './fb-message.stories.scss';

const meta: Meta<typeof FbMessage> = {
	title: 'Components/Feedback/Message',
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbMessage>;

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-button :plain="true" @click="open">Show message</fb-button>
	<fb-button :plain="true" @click="openVn">VNode</fb-button>
</template>

<script lang="ts" setup>
import { h } from 'vue';
import { FbMessage } from '@fastybird/web-ui-library';

const open = (): void => {
	FbMessage.success('This is a message.');
}

const openVn = () => {
	FbMessage({
		message: h('p', { style: 'line-height: 1; font-size: 14px' }, [
			h('span', null, 'Message can be '),
			h('i', { style: 'color: teal' }, 'VNode'),
		]),
	});
}
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbButton },
		methods: {
			open: () => {
				FbMessage.success('This is a message.');
			},
			openVn: () => {
				FbMessage({
					message: h('p', { style: 'line-height: 1; font-size: 14px' }, [
						h('span', null, 'Message can be '),
						h('i', { style: 'color: teal' }, 'VNode'),
					]),
				});
			},
		},
		template: `
<fb-button :plain="true" @click="open">Show message</fb-button>
<fb-button :plain="true" @click="openVn">VNode</fb-button>`,
	}),
};

export const Types: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-button :plain="true" @click="open2">Success</fb-button>
	<fb-button :plain="true" @click="open3">Warning</fb-button>
	<fb-button :plain="true" @click="open1">Message</fb-button>
	<fb-button :plain="true" @click="open4">Error</fb-button>
</template>

<script lang="ts" setup>
import { FbMessage, VariantTypes } from '@fastybird/web-ui-library';

const open1 = (): void => {
	FbMessage.success('This is a message.');
}

const open2 = (): void => {
	FbMessage({
		message: 'Congrats, this is a success message.',
		type: VariantTypes.SUCCESS,
	});
}

const open3 = (): void => {
	FbMessage({
		message: 'Warning, this is a warning message.',
		type: VariantTypes.WARNING,
	});
}

const open4 = (): void => {
	FbMessage.error('Oops, this is a error message.');
}
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbButton },
		methods: {
			open1: () => {
				FbMessage.success('This is a message.');
			},
			open2: () => {
				FbMessage({
					message: 'Congrats, this is a success message.',
					type: VariantTypes.SUCCESS,
				});
			},
			open3: () => {
				FbMessage({
					message: 'Warning, this is a warning message.',
					type: VariantTypes.WARNING,
				});
			},
			open4: () => {
				FbMessage.error('Oops, this is a error message.');
			},
		},
		template: `
<fb-button :plain="true" @click="open2">Success</fb-button>
<fb-button :plain="true" @click="open3">Warning</fb-button>
<fb-button :plain="true" @click="open1">Message</fb-button>
<fb-button :plain="true" @click="open4">Error</fb-button>`,
	}),
};

export const Closable: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-button :plain="true" @click="open1">Message</fb-button>
	<fb-button :plain="true" @click="open2">Success</fb-button>
	<fb-button :plain="true" @click="open3">Warning</fb-button>
	<fb-button :plain="true" @click="open4">Error</fb-button>
</template>

<script lang="ts" setup>
import { FbMessage, VariantTypes } from '@fastybird/web-ui-library';

const open1 = (): void => {
	FbMessage({
		showClose: true,
		message: 'This is a message.',
	});
}

const open2 = (): void => {
	FbMessage({
		showClose: true,
		message: 'Congrats, this is a success message.',
		type: VariantTypes.SUCCESS,
	});
}

const open3 = (): void => {
	FbMessage({
		showClose: true,
		message: 'Warning, this is a warning message.',
		type: VariantTypes.WARNING,
	});
}

const open4 = (): void => {
	FbMessage({
		showClose: true,
		message: 'Oops, this is a error message.',
		type: VariantTypes.ERROR,
	});
}
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbButton },
		methods: {
			open1: () => {
				FbMessage({
					showClose: true,
					message: 'This is a message.',
				});
			},
			open2: () => {
				FbMessage({
					showClose: true,
					message: 'Congrats, this is a success message.',
					type: VariantTypes.SUCCESS,
				});
			},
			open3: () => {
				FbMessage({
					showClose: true,
					message: 'Warning, this is a warning message.',
					type: VariantTypes.WARNING,
				});
			},
			open4: () => {
				FbMessage({
					showClose: true,
					message: 'Oops, this is a error message.',
					type: VariantTypes.ERROR,
				});
			},
		},
		template: `
<fb-button :plain="true" @click="open1">Message</fb-button>
<fb-button :plain="true" @click="open2">Success</fb-button>
<fb-button :plain="true" @click="open3">Warning</fb-button>
<fb-button :plain="true" @click="open4">Error</fb-button>`,
	}),
};

export const CenteredText: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-button plain @click="openCenter">Centered text</fb-button>
</template>

<script lang="ts" setup>
import { FbMessage } from '@fastybird/web-ui-library';

const openCenter = (): void => {
	FbMessage({
		showClose: true,
		message: 'Centered text',
		center: true,
	});
}
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbButton },
		methods: {
			openCenter: () => {
				FbMessage({
					showClose: true,
					message: 'Centered text',
					center: true,
				});
			},
		},
		template: `
<fb-button plain @click="openCenter">Centered text</fb-button>`,
	}),
};

export const HtmlContent: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-button :plain="true" @click="openHTML">Use HTML string</fb-button>
</template>

<script lang="ts" setup>
import { FbMessage } from '@fastybird/web-ui-library';

const openHTML = (): void => {
	FbMessage({
		dangerouslyUseHTMLString: true,
		message: '<strong>This is <i>HTML</i> string</strong>',
	});
}
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbButton },
		methods: {
			openHTML: () => {
				FbMessage({
					dangerouslyUseHTMLString: true,
					message: '<strong>This is <i>HTML</i> string</strong>',
				});
			},
		},
		template: `
<fb-button :plain="true" @click="openHTML">Use HTML string</fb-button>`,
	}),
};

export const Grouping: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-button :plain="true" @click="open">Show message</fb-button>
</template>

<script lang="ts" setup>
import { FbMessage } from '@fastybird/web-ui-library';

const open = (): void => {
  FbMessage({
    message: 'This is a message.',
    grouping: true,
    type: VariantTypes.SUCCESS,
  });
}
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbButton },
		methods: {
			open: () => {
				FbMessage({
					message: 'This is a message.',
					grouping: true,
					type: VariantTypes.SUCCESS,
				});
			},
		},
		template: `
<fb-button :plain="true" @click="open">Show message</fb-button>`,
	}),
};
