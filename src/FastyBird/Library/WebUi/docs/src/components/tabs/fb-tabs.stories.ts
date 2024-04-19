import { Meta, StoryObj } from '@storybook/vue3';
import {
	FbTabs,
	FbTabPane,
	FbRadioGroup,
	FbRadioButton,
	FbIcon,
	FbButton,
	TabsPaneContext,
	TabPaneName,
	TabNavTypeTypes,
	TabPositionTypes,
} from '@fastybird/web-ui-components';
import { FasCalendar, FasCheck } from '@fastybird/web-ui-icons';

import './fb-tabs.stories.scss';
import { ref } from 'vue';

const meta: Meta<typeof FbTabs> = {
	component: FbTabs,
	title: 'Components/Navigation/Tabs',
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbTabs>;

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tabs
		v-model="activeName"
		class="demo-tabs"
		@tab-click="handleClick"
	>
		<fb-tab-pane label="User" name="first">User</fb-tab-pane>
		<fb-tab-pane label="Config" name="second">Config</fb-tab-pane>
		<fb-tab-pane label="Role" name="third">Role</fb-tab-pane>
		<fb-tab-pane label="Task" name="fourth">Task</fb-tab-pane>
	</fb-tabs>
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import type { TabsPaneContext } from '@fastybird/web-ui-library'

const activeName = ref('first');

const handleClick = (tab: TabsPaneContext, event: Event): void => {
	console.log(tab, event);
}
</script>

<style scoped>
.demo-tabs > .fb-tabs__content {
	padding: 32px;
	color: #6b778c;
	font-size: 32px;
	font-weight: 600;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTabs, FbTabPane },
		setup: () => {
			const activeName = ref('first');

			const handleClick = (tab: TabsPaneContext, event: Event) => {
				console.log(tab, event);
			};

			return {
				activeName,
				handleClick,
			};
		},
		template: `
<div class="fb-tabs-story-block">
	<div class="fb-tabs-story-block__item">
		<fb-tabs v-model="activeName" @tab-click="handleClick">
			<fb-tab-pane label="User" name="first">User</fb-tab-pane>
			<fb-tab-pane label="Config" name="second">Config</fb-tab-pane>
			<fb-tab-pane label="Role" name="third">Role</fb-tab-pane>
			<fb-tab-pane label="Task" name="fourth">Task</fb-tab-pane>
		</fb-tabs>
	</div>
</div>`,
	}),
};

export const CardStyle: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tabs
		v-model="activeName"
		type="${TabNavTypeTypes.CARD}"
		class="demo-tabs"
		@tab-click="handleClick"
	>
		<fb-tab-pane label="User" name="first">User</fb-tab-pane>
		<fb-tab-pane label="Config" name="second">Config</fb-tab-pane>
		<fb-tab-pane label="Role" name="third">Role</fb-tab-pane>
		<fb-tab-pane label="Task" name="fourth">Task</fb-tab-pane>
	</fb-tabs>
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import type { TabsPaneContext } from '@fastybird/web-ui-library'

const activeName = ref('first');

const handleClick = (tab: TabsPaneContext, event: Event): void => {
	console.log(tab, event);
}
</script>

<style scoped>
.demo-tabs > .fb-tabs__content {
  padding: 32px;
  color: #6b778c;
  font-size: 32px;
  font-weight: 600;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTabs, FbTabPane },
		setup: () => {
			const activeName = ref('first');

			const handleClick = (tab: TabsPaneContext, event: Event) => {
				console.log(tab, event);
			};

			return {
				activeName,
				handleClick,
			};
		},
		template: `
<div class="fb-tabs-story-block">
	<div class="fb-tabs-story-block__item">
		<fb-tabs
			v-model="activeName"
			type="${TabNavTypeTypes.CARD}"
			@tab-click="handleClick"
		>
			<fb-tab-pane label="User" name="first">User</fb-tab-pane>
			<fb-tab-pane label="Config" name="second">Config</fb-tab-pane>
			<fb-tab-pane label="Role" name="third">Role</fb-tab-pane>
			<fb-tab-pane label="Task" name="fourth">Task</fb-tab-pane>
		</fb-tabs>
	</div>
</div>`,
	}),
};

export const BorderCard: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tabs
		v-model="activeName"
		type="${TabNavTypeTypes.BORDER_CARD}"
		class="demo-tabs"
		@tab-click="handleClick"
	>
		<fb-tab-pane label="User" name="first">User</fb-tab-pane>
		<fb-tab-pane label="Config" name="second">Config</fb-tab-pane>
		<fb-tab-pane label="Role" name="third">Role</fb-tab-pane>
		<fb-tab-pane label="Task" name="fourth">Task</fb-tab-pane>
	</fb-tabs>
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import type { TabsPaneContext } from '@fastybird/web-ui-library'

const activeName = ref('first');

const handleClick = (tab: TabsPaneContext, event: Event): void => {
	console.log(tab, event);
}
</script>

<style scoped>
.demo-tabs > .fb-tabs__content {
  padding: 32px;
  color: #6b778c;
  font-size: 32px;
  font-weight: 600;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTabs, FbTabPane },
		setup: () => {
			const activeName = ref('first');

			const handleClick = (tab: TabsPaneContext, event: Event) => {
				console.log(tab, event);
			};

			return {
				activeName,
				handleClick,
			};
		},
		template: `
<div class="fb-tabs-story-block">
	<div class="fb-tabs-story-block__item">
		<fb-tabs
			v-model="activeName"
			type="${TabNavTypeTypes.BORDER_CARD}"
			@tab-click="handleClick"
		>
			<fb-tab-pane label="User" name="first">User</fb-tab-pane>
			<fb-tab-pane label="Config" name="second">Config</fb-tab-pane>
			<fb-tab-pane label="Role" name="third">Role</fb-tab-pane>
			<fb-tab-pane label="Task" name="fourth">Task</fb-tab-pane>
		</fb-tabs>
	</div>
</div>`,
	}),
};

export const TabPosition: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-radio-group v-model="tabPosition">
		<fb-radio-button value="${TabPositionTypes.TOP}">top</fb-radio-button>
		<fb-radio-button value="${TabPositionTypes.RIGHT}">right</fb-radio-button>
		<fb-radio-button value="${TabPositionTypes.BOTTOM}">bottom</fb-radio-button>
		<fb-radio-button value="${TabPositionTypes.LEFT}">left</fb-radio-button>
	</fb-radio-group>

	<fb-tabs :tab-position="tabPosition" class="demo-tabs">
		<fb-tab-pane label="User">User</fb-tab-pane>
		<fb-tab-pane label="Config">Config</fb-tab-pane>
		<fb-tab-pane label="Role">Role</fb-tab-pane>
		<fb-tab-pane label="Task">Task</fb-tab-pane>
	</fb-tabs>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const tabPosition = ref('left');
</script>

<style scoped>
.demo-tabs > .fb-tabs__content {
  padding: 32px;
  color: #6b778c;
  font-size: 32px;
  font-weight: 600;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTabs, FbTabPane, FbRadioGroup, FbRadioButton },
		setup: () => {
			const tabPosition = ref('left');

			return {
				tabPosition,
			};
		},
		template: `
<div class="fb-tabs-story-block">
	<div class="fb-tabs-story-block__controls">
		<fb-radio-group v-model="tabPosition">
			<fb-radio-button value="${TabPositionTypes.TOP}">top</fb-radio-button>
			<fb-radio-button value="${TabPositionTypes.RIGHT}">right</fb-radio-button>
			<fb-radio-button value="${TabPositionTypes.BOTTOM}">bottom</fb-radio-button>
			<fb-radio-button value="${TabPositionTypes.LEFT}">left</fb-radio-button>
		</fb-radio-group>
	</div>

	<div class="fb-tabs-story-block__item">
		<fb-tabs :tab-position="tabPosition">
			<fb-tab-pane label="User">User</fb-tab-pane>
			<fb-tab-pane label="Config">Config</fb-tab-pane>
			<fb-tab-pane label="Role">Role</fb-tab-pane>
			<fb-tab-pane label="Task">Task</fb-tab-pane>
		</fb-tabs>
	</div>
</div>`,
	}),
};

export const CustomTab: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tabs type="${TabNavTypeTypes.BORDER_CARD}" class="demo-tabs">
		<fb-tab-pane>
			<template #label>
				<fb-icon><fas-calendar /></fb-icon>
				<span>Route</span>
			</template>

			Route
		</fb-tab-pane>
		<fb-tab-pane label="Config">Config</fb-tab-pane>
		<fb-tab-pane label="Role">Role</fb-tab-pane>
		<fb-tab-pane label="Task">Task</fb-tab-pane>
	</fb-tabs>
</template>

<script lang="ts" setup>
import { FasCalendar } from '@fastybird/web-ui-icons';
</script>

<style scoped>
.demo-tabs > .fb-tabs__content {
	padding: 32px;
	color: #6b778c;
	font-size: 32px;
	font-weight: 600;
}

.demo-tabs .custom-tabs-label .fb-icon {
	vertical-align: middle;
}

.demo-tabs .custom-tabs-label span {
	vertical-align: middle;
	margin-left: 4px;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTabs, FbTabPane, FbIcon, FasCalendar },
		setup: () => {
			return {
				FasCalendar,
			};
		},
		template: `
<div class="fb-tabs-story-block">
	<div class="fb-tabs-story-block__item">
		<fb-tabs type="${TabNavTypeTypes.BORDER_CARD}" class="fb-tabs-story-block__custom-tabs-label">
			<fb-tab-pane>
				<template #label>
					<fb-icon><fas-calendar /></fb-icon>
					<span>Route</span>
				</template>

				Route
			</fb-tab-pane>
			<fb-tab-pane label="Config">Config</fb-tab-pane>
			<fb-tab-pane label="Role">Role</fb-tab-pane>
			<fb-tab-pane label="Task">Task</fb-tab-pane>
		</fb-tabs>
	</div>
</div>`,
	}),
};

export const AddCloseTab: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tabs
		v-model="editableTabsValue"
		type="${TabNavTypeTypes.CARD}"
		editable
		class="demo-tabs"
		@edit="handleTabsEdit"
	>
		<fb-tab-pane
			v-for="item in editableTabs"
			:key="item.name"
			:label="item.title"
			:name="item.name"
		>
			{{ item.content }}
		</fb-tab-pane>
	</fb-tabs>
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import type { TabPaneName } from '@fastybird/web-ui-library';

let tabIndex = 2;

const editableTabsValue = ref('2');

const editableTabs = ref([
	{
		title: 'Tab 1',
		name: '1',
		content: 'Tab 1 content',
	},
	{
		title: 'Tab 2',
		name: '2',
		content: 'Tab 2 content',
	},
]);

const handleTabsEdit = (
	targetName: TabPaneName | undefined,
	action: 'remove' | 'add',
) => {
	if (action === 'add') {
		const newTabName = \`\$\{++tabIndex\}\`;

		editableTabs.value.push({
			title: 'New Tab',
			name: newTabName,
			content: 'New Tab content',
		});

		editableTabsValue.value = newTabName;
	} else if (action === 'remove') {
		const tabs = editableTabs.value;

		let activeName = editableTabsValue.value;

		if (activeName === targetName) {
			tabs.forEach((tab, index) => {
				if (tab.name === targetName) {
					const nextTab = tabs[index + 1] || tabs[index - 1];

					if (nextTab) {
						activeName = nextTab.name;
					}
				}
			})
		}

		editableTabsValue.value = activeName;
		editableTabs.value = tabs.filter((tab) => tab.name !== targetName);
	}
}
</script>

<style scoped>
.demo-tabs > .fb-tabs__content {
	padding: 32px;
	color: #6b778c;
	font-size: 32px;
	font-weight: 600;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTabs, FbTabPane },
		setup: () => {
			let tabIndex = 2;

			const editableTabsValue = ref('2');

			const editableTabs = ref([
				{
					title: 'Tab 1',
					name: '1',
					content: 'Tab 1 content',
				},
				{
					title: 'Tab 2',
					name: '2',
					content: 'Tab 2 content',
				},
			]);

			const handleTabsEdit = (targetName: TabPaneName | undefined, action: 'remove' | 'add') => {
				if (action === 'add') {
					const newTabName = `${++tabIndex}`;

					editableTabs.value.push({
						title: 'New Tab',
						name: newTabName,
						content: 'New Tab content',
					});

					editableTabsValue.value = newTabName;
				} else if (action === 'remove') {
					const tabs = editableTabs.value;

					let activeName = editableTabsValue.value;

					if (activeName === targetName) {
						tabs.forEach((tab, index) => {
							if (tab.name === targetName) {
								const nextTab = tabs[index + 1] || tabs[index - 1];

								if (nextTab) {
									activeName = nextTab.name;
								}
							}
						});
					}

					editableTabsValue.value = activeName;
					editableTabs.value = tabs.filter((tab) => tab.name !== targetName);
				}
			};

			return {
				tabIndex,
				editableTabsValue,
				editableTabs,
				handleTabsEdit,
			};
		},
		template: `
<div class="fb-tabs-story-block">
	<div class="fb-tabs-story-block__item">
		<fb-tabs
			v-model="editableTabsValue"
			type="${TabNavTypeTypes.CARD}"
			editable
			@edit="handleTabsEdit"
		>
			<fb-tab-pane
				v-for="item in editableTabs"
				:key="item.name"
				:label="item.title"
				:name="item.name"
			>
				{{ item.content }}
			</fb-tab-pane>
		</fb-tabs>
	</div>
</div>`,
	}),
};

export const CustomAddIcon: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-tabs
		v-model="editableTabsValue"
		type="${TabNavTypeTypes.CARD}"
		editable
		class="demo-tabs"
		@edit="handleTabsEdit"
	>
		<template #add-icon>
			<fb-icon><fas-check /></fb-icon>
		</template>

		<fb-tab-pane
			v-for="item in editableTabs"
			:key="item.name"
			:label="item.title"
			:name="item.name"
		>
			{{ item.content }}
		</fb-tab-pane>
	</fb-tabs>
</template>
<script lang="ts" setup>
import { ref } from 'vue'
import type { TabPaneName } from '@fastybird/web-ui-library';

let tabIndex = 2;

const editableTabsValue = ref('2');

const editableTabs = ref([
	{
		title: 'Tab 1',
		name: '1',
		content: 'Tab 1 content',
	},
	{
		title: 'Tab 2',
		name: '2',
		content: 'Tab 2 content',
	},
]);

const handleTabsEdit = (
	targetName: TabPaneName | undefined,
	action: 'remove' | 'add',
) => {
	if (action === 'add') {
		const newTabName = \`\$\{++tabIndex\}\`;

		editableTabs.value.push({
			title: 'New Tab',
			name: newTabName,
			content: 'New Tab content',
		});

		editableTabsValue.value = newTabName;
	} else if (action === 'remove') {
		const tabs = editableTabs.value;

		let activeName = editableTabsValue.value;

		if (activeName === targetName) {
			tabs.forEach((tab, index) => {
				if (tab.name === targetName) {
					const nextTab = tabs[index + 1] || tabs[index - 1]
					if (nextTab) {
						activeName = nextTab.name
					}
				}
			});
		}

		editableTabsValue.value = activeName;
		editableTabs.value = tabs.filter((tab) => tab.name !== targetName);
	}
}
</script>

<style>
.demo-tabs > .fb-tabs__content {
	padding: 32px;
	color: #6b778c;
	font-size: 32px;
	font-weight: 600;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTabs, FbTabPane, FbIcon, FasCheck },
		setup: () => {
			let tabIndex = 2;

			const editableTabsValue = ref('2');

			const editableTabs = ref([
				{
					title: 'Tab 1',
					name: '1',
					content: 'Tab 1 content',
				},
				{
					title: 'Tab 2',
					name: '2',
					content: 'Tab 2 content',
				},
			]);

			const handleTabsEdit = (targetName: TabPaneName | undefined, action: 'remove' | 'add') => {
				if (action === 'add') {
					const newTabName = `${++tabIndex}`;

					editableTabs.value.push({
						title: 'New Tab',
						name: newTabName,
						content: 'New Tab content',
					});

					editableTabsValue.value = newTabName;
				} else if (action === 'remove') {
					const tabs = editableTabs.value;

					let activeName = editableTabsValue.value;

					if (activeName === targetName) {
						tabs.forEach((tab, index) => {
							if (tab.name === targetName) {
								const nextTab = tabs[index + 1] || tabs[index - 1];

								if (nextTab) {
									activeName = nextTab.name;
								}
							}
						});
					}

					editableTabsValue.value = activeName;
					editableTabs.value = tabs.filter((tab) => tab.name !== targetName);
				}
			};

			return {
				tabIndex,
				editableTabsValue,
				editableTabs,
				handleTabsEdit,
			};
		},
		template: `
<div class="fb-tabs-story-block">
	<div class="fb-tabs-story-block__item">
		<fb-tabs
			v-model="editableTabsValue"
			type="${TabNavTypeTypes.CARD}"
			editable
			@edit="handleTabsEdit"
		>
			<template #add-icon>
				<fb-icon><fas-check /></fb-icon>
			</template>

			<fb-tab-pane
				v-for="item in editableTabs"
				:key="item.name"
				:label="item.title"
				:name="item.name"
			>
				{{ item.content }}
			</fb-tab-pane>
		</fb-tabs>
	</div>
</div>`,
	}),
};

export const CustomAddTrigger: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-button size="small" @click="addTab(editableTabsValue)">
		Add new tab
	</fb-button>

	<fb-tabs
		v-model="editableTabsValue"
		type="${TabNavTypeTypes.CARD}"
		closable
		class="demo-tabs"
		@tab-remove="removeTab"
	>
		<fb-tab-pane
			v-for="item in editableTabs"
			:key="item.name"
			:label="item.title"
			:name="item.name"
		>
			{{ item.content }}
		</fb-tab-pane>
	</fb-tabs>
</template>
<script lang="ts" setup>
import { ref } from 'vue';

let tabIndex = 2;

const editableTabsValue = ref('2');

const editableTabs = ref([
	{
		title: 'Tab 1',
		name: '1',
		content: 'Tab 1 content',
	},
	{
		title: 'Tab 2',
		name: '2',
		content: 'Tab 2 content',
	},
]);

const addTab = (targetName: string) => {
	const newTabName = \`\$\{++tabIndex\}\`;

	editableTabs.value.push({
		title: 'New Tab',
		name: newTabName,
		content: 'New Tab content',
	});

	editableTabsValue.value = newTabName;
}

const removeTab = (targetName: string) => {
	const tabs = editableTabs.value;
	let activeName = editableTabsValue.value;

	if (activeName === targetName) {
		tabs.forEach((tab, index) => {
			if (tab.name === targetName) {
				const nextTab = tabs[index + 1] || tabs[index - 1]
				if (nextTab) {
					activeName = nextTab.name
				}
			}
		});
	}

	editableTabsValue.value = activeName;
	editableTabs.value = tabs.filter((tab) => tab.name !== targetName);
}
</script>

<style>
.demo-tabs > .el-tabs__content {
	padding: 32px;
	color: #6b778c;
	font-size: 32px;
	font-weight: 600;
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbTabs, FbTabPane, FbButton },
		setup: () => {
			let tabIndex = 2;

			const editableTabsValue = ref('2');

			const editableTabs = ref([
				{
					title: 'Tab 1',
					name: '1',
					content: 'Tab 1 content',
				},
				{
					title: 'Tab 2',
					name: '2',
					content: 'Tab 2 content',
				},
			]);

			const addTab = (targetName: string) => {
				const newTabName = `${++tabIndex}`;

				editableTabs.value.push({
					title: 'New Tab',
					name: newTabName,
					content: 'New Tab content',
				});
				editableTabsValue.value = newTabName;
			};

			const removeTab = (targetName: string) => {
				const tabs = editableTabs.value;

				let activeName = editableTabsValue.value;

				if (activeName === targetName) {
					tabs.forEach((tab, index) => {
						if (tab.name === targetName) {
							const nextTab = tabs[index + 1] || tabs[index - 1];

							if (nextTab) {
								activeName = nextTab.name;
							}
						}
					});
				}

				editableTabsValue.value = activeName;
				editableTabs.value = tabs.filter((tab) => tab.name !== targetName);
			};

			return {
				tabIndex,
				editableTabsValue,
				editableTabs,
				addTab,
				removeTab,
			};
		},
		template: `
<div class="fb-tabs-story-block">
	<div class="fb-tabs-story-block__controls">
		<fb-button size="small" @click="addTab(editableTabsValue)">
			Add new tab
		</fb-button>
	</div>

	<div class="fb-tabs-story-block__item">
		<fb-tabs
			v-model="editableTabsValue"
			type="${TabNavTypeTypes.CARD}"
			closable
			@tab-remove="removeTab"
		>
			<fb-tab-pane
				v-for="item in editableTabs"
				:key="item.name"
				:label="item.title"
				:name="item.name"
			>
				{{ item.content }}
			</fb-tab-pane>
		</fb-tabs>
	</div>
</div>`,
	}),
};
