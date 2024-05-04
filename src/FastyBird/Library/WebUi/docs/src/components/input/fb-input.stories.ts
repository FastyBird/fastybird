import { ref } from "vue";

import { Meta, StoryObj } from "@storybook/vue3";
import { FbInput, FbSelect, FbOption, FbIcon, FbButton } from "@fastybird/web-ui-components";
import { FasCalendar, FasMagnifyingGlass } from "@fastybird/web-ui-icons";

import "./fb-input.stories.scss";

const meta: Meta<typeof FbInput> = {
    component: FbInput,
    title: "Components/Form/Input",
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbInput>;

export const BasicUsage: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-input v-model="input" placeholder="Please input" />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const input = ref('');
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbInput },
        setup: () => {
            const input = ref("");

            return {
                input,
            };
        },
        template: `
<div class="fb-input-story-block">
	<fb-input v-model="input" placeholder="Please input" />
</div>`,
    }),
};

export const Disabled: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-input v-model="input" disabled placeholder="Please input" />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const input = ref('');
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbInput },
        setup: () => {
            const input = ref("");

            return {
                input,
            };
        },
        template: `
<div class="fb-input-story-block">
	<fb-input v-model="input" disabled placeholder="Please input" />
</div>`,
    }),
};

export const Clearable: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-input v-model="input" placeholder="Please input" clearable />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const input = ref('');
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbInput },
        setup: () => {
            const input = ref("");

            return {
                input,
            };
        },
        template: `
<div class="fb-input-story-block">
	<fb-input v-model="input" placeholder="Please input" clearable />
</div>`,
    }),
};

export const Formatter: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-input
		v-model="input"
		placeholder="Please input"
		:formatter="(value) => \`$ \$\{value\}\`.replace(/\\B(?=(\\d{3})+(?!\\d))/g, ',')"
		:parser="(value) => value.replace(/\\$\\s?|(,*)/g, '')"
	/>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const input = ref('');
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbInput },
        setup: () => {
            const input = ref("");

            return {
                input,
            };
        },
        template: `
<div class="fb-input-story-block">
	<fb-input
		v-model="input"
		placeholder="Please input"
		:formatter="(value) => \`$ \$\{value\}\`.replace(/\\B(?=(\\d{3})+(?!\\d))/g, ',')"
		:parser="(value) => value.replace(/\\$\\s?|(,*)/g, '')"
	/>
</div>`,
    }),
};

export const Password: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-input v-model="input" type="password" placeholder="Please input password" show-password />
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const input = ref('');
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbInput },
        setup: () => {
            const input = ref("");

            return {
                input,
            };
        },
        template: `
<div class="fb-input-story-block">
	<fb-input v-model="input" type="password" placeholder="Please input password" show-password />
</div>`,
    }),
};

export const WithIcon: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<div>
		<span>Using attributes</span>
		<fb-input
			v-model="input1"
			placeholder="Pick a date"
			:suffix-icon="FasCalendar"
		/>
		<fb-input
			v-model="input2"
			placeholder="Type something"
			:prefix-icon="FasMagnifyingGlass"
		/>
	</div>
	<div>
		<span>Using slots</span>
		<fb-input v-model="input3" placeholder="Pick a date">
			<template #suffix>
				<fb-icon><fas-calendar /></fb-icon>
			</template>
		</fb-input>
		<fb-input v-model="input4" placeholder="Type something">
			<template #prefix>
				<fb-icon><fas-magnifying-glass /></fb-icon>
			</template>
		</fb-input>
	</div>
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import { FasCalendar, FasMagnifyingGlass } from '@fastybird/web-ui-icons';

const input1 = ref('');
const input2 = ref('');
const input3 = ref('');
const input4 = ref('');
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbInput, FbIcon, FasCalendar, FasMagnifyingGlass },
        setup: () => {
            const input1 = ref("");
            const input2 = ref("");
            const input3 = ref("");
            const input4 = ref("");

            return {
                input1,
                input2,
                input3,
                input4,
                FasCalendar,
                FasMagnifyingGlass,
            };
        },
        template: `
<div class="fb-input-story-block">
	<div class="fb-input-story-block__form-row">
		<span>Using attributes</span>
		<fb-input
			v-model="input1"
			placeholder="Pick a date"
			:suffix-icon="FasCalendar"
		/>
		<fb-input
			v-model="input2"
			placeholder="Type something"
			:prefix-icon="FasMagnifyingGlass"
		/>
	</div>
	<div class="fb-input-story-block__form-row">
		<span>Using slots</span>
		<fb-input v-model="input3" placeholder="Pick a date">
			<template #suffix>
				<fb-icon><fas-calendar /></fb-icon>
			</template>
		</fb-input>
		<fb-input v-model="input4" placeholder="Type something">
			<template #prefix>
				<fb-icon><fas-magnifying-glass /></fb-icon>
			</template>
		</fb-input>
	</div>
</div>`,
    }),
};

export const Textarea: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-input
		v-model="textarea"
		:rows="2"
		type="textarea"
		placeholder="Please input"
	/>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const textarea = ref('');
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbInput },
        setup: () => {
            const textarea = ref("");

            return {
                textarea,
            };
        },
        template: `
<div class="fb-input-story-block">
	<fb-input
		v-model="textarea"
		:rows="2"
		type="textarea"
		placeholder="Please input"
	/>
</div>`,
    }),
};

export const AutosizeTextarea: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-input
		v-model="textarea1"
		autosize
		type="textarea"
		placeholder="Please input"
	/>

	<fb-input
		v-model="textarea2"
		:autosize="{ minRows: 2, maxRows: 4 }"
		type="textarea"
		placeholder="Please input"
	/>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const textarea1 = ref('');
const textarea2 = ref('');
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbInput },
        setup: () => {
            const textarea1 = ref("");
            const textarea2 = ref("");

            return {
                textarea1,
                textarea2,
            };
        },
        template: `
<div class="fb-input-story-block">
	<fb-input
		v-model="textarea1"
		autosize
		type="textarea"
		placeholder="Please input"
	/>

	<div class="fb-input-story-block__divider" />

	<fb-input
		v-model="textarea2"
		:autosize="{ minRows: 2, maxRows: 4 }"
		type="textarea"
		placeholder="Please input"
	/>
</div>`,
    }),
};

export const Mixed: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<div>
		<fb-input v-model="input1" placeholder="Please input">
			<template #prepend>Http://</template>
		</fb-input>
	</div>
	<div>
		<fb-input v-model="input2" placeholder="Please input">
			<template #append>.com</template>
		</fb-input>
	</div>
	<div>
		<fb-input v-model="input3" placeholder="Please input" class="input-with-select">
			<template #prepend>
				<fb-select v-model="select" placeholder="Select">
					<fb-option label="Restaurant" value="1" />
					<fb-option label="Order No." value="2" />
					<fb-option label="Tel" value="3" />
				</fb-select>
			</template>
			<template #append>
				<fb-button :icon="FasMagnifyingGlass" />
			</template>
		</fb-input>
	</div>
	<div>
		<fb-input v-model="input3" placeholder="Please input" class="input-with-select">
			<template #prepend>
				<fb-button :icon="FasMagnifyingGlass" />
			</template>
			<template #append>
				<fb-select v-model="select" placeholder="Select">
					<fb-option label="Restaurant" value="1" />
					<fb-option label="Order No." value="2" />
					<fb-option label="Tel" value="3" />
				</fb-select>
			</template>
		</fb-input>
	</div>
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import { FasMagnifyingGlass } from '@fastybird/web-ui-icons';

const input1 = ref('');
const input2 = ref('');
const input3 = ref('');
const select = ref('');
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbInput, FbSelect, FbOption, FbButton, FasMagnifyingGlass },
        setup: () => {
            const input1 = ref("");
            const input2 = ref("");
            const input3 = ref("");
            const select = ref("");

            return {
                input1,
                input2,
                input3,
                select,
                FasMagnifyingGlass,
            };
        },
        template: `
<div class="fb-input-story-block">
	<div class="fb-input-story-block__item">
		<fb-input
			v-model="input1"
			class="large-input"
			placeholder="Please input"
		>
			<template #prepend>Http://</template>
		</fb-input>
	</div>
	<div class="fb-input-story-block__item">
		<fb-input
			v-model="input2"
			class="large-input"
			placeholder="Please input"
		>
			<template #append>.com</template>
		</fb-input>
	</div>
	<div class="fb-input-story-block__item">
		<fb-input
			v-model="input3"
			class="large-input"
			placeholder="Please input"
		>
			<template #prepend>
				<fb-select v-model="select" placeholder="Select">
					<fb-option label="Restaurant" value="1" />
					<fb-option label="Order No." value="2" />
					<fb-option label="Tel" value="3" />
				</fb-select>
			</template>
			<template #append>
				<fb-button :icon="FasMagnifyingGlass" />
			</template>
		</fb-input>
	</div>
	<div class="fb-input-story-block__item">
		<fb-input
			v-model="input3"
			class="large-input"
			placeholder="Please input"
		>
			<template #prepend>
				<fb-button :icon="FasMagnifyingGlass" />
			</template>
			<template #append>
				<fb-select v-model="select" placeholder="Select">
					<fb-option label="Restaurant" value="1" />
					<fb-option label="Order No." value="2" />
					<fb-option label="Tel" value="3" />
				</fb-select>
			</template>
		</fb-input>
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
	<div>
		<fb-input
			v-model="input1"
			size="large"
			placeholder="Please Input"
		/>
		<fb-input
			v-model="input2"
			placeholder="Please Input"
		/>
		<fb-input
			v-model="input3"
			size="small"
			placeholder="Please Input"
		/>
	</div>
	<div>
		<fb-input
			v-model="input1"
			size="large"
			placeholder="Please Input"
			:suffix-icon="FasMagnifyingGlass"
		/>
		<fb-input
			v-model="input2"
			placeholder="Please Input"
			:suffix-icon="FasMagnifyingGlass"
		/>
		<fb-input
			v-model="input3"
			size="small"
			placeholder="Please Input"
			:suffix-icon="FasMagnifyingGlass"
		/>
	</div>
	<div>
		<fb-input
			v-model="input1"
			size="large"
			placeholder="Please Input"
			:prefix-icon="FasMagnifyingGlass"
		/>
		<fb-input
			v-model="input2"
			placeholder="Please Input"
			:prefix-icon="FasMagnifyingGlass"
		/>
		<fb-input
			v-model="input3"
			size="small"
			placeholder="Please Input"
			:prefix-icon="FasMagnifyingGlass"
		/>
	</div>
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import { FasMagnifyingGlass } from '@fastybird/web-ui-icons';

const input1 = ref('');
const input2 = ref('');
const input3 = ref('');
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbInput, FasMagnifyingGlass },
        setup: () => {
            const input1 = ref("");
            const input2 = ref("");
            const input3 = ref("");

            return {
                input1,
                input2,
                input3,
                FasMagnifyingGlass,
            };
        },
        template: `
<div class="fb-input-story-block">
	<div class="fb-input-story-block__form-row">
		<fb-input
			v-model="input1"
			size="large"
			placeholder="Please Input"
		/>
		<fb-input
			v-model="input2"
			placeholder="Please Input"
		/>
		<fb-input
			v-model="input3"
			size="small"
			placeholder="Please Input"
		/>
	</div>
	<div class="fb-input-story-block__form-row">
		<fb-input
			v-model="input1"
			size="large"
			placeholder="Please Input"
			:suffix-icon="FasMagnifyingGlass"
		/>
		<fb-input
			v-model="input2"
			placeholder="Please Input"
			:suffix-icon="FasMagnifyingGlass"
		/>
		<fb-input
			v-model="input3"
			size="small"
			placeholder="Please Input"
			:suffix-icon="FasMagnifyingGlass"
		/>
	</div>
	<div class="fb-input-story-block__form-row">
		<fb-input
			v-model="input1"
			size="large"
			placeholder="Please Input"
			:prefix-icon="FasMagnifyingGlass"
		/>
		<fb-input
			v-model="input2"
			placeholder="Please Input"
			:prefix-icon="FasMagnifyingGlass"
		/>
		<fb-input
			v-model="input3"
			size="small"
			placeholder="Please Input"
			:prefix-icon="FasMagnifyingGlass"
		/>
	</div>
</div>`,
    }),
};

export const LimitLength: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-input
		v-model="text"
		maxlength="10"
		placeholder="Please input"
		show-word-limit
		type="text"
	/>

	<fb-input
		v-model="textarea"
		maxlength="30"
		placeholder="Please input"
		show-word-limit
		type="textarea"
	/>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const text = ref('');
const textarea = ref('');
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbInput },
        setup: () => {
            const text = ref("");
            const textarea = ref("");

            return {
                text,
                textarea,
            };
        },
        template: `
<div class="fb-input-story-block">
	<fb-input
		v-model="text"
		maxlength="10"
		placeholder="Please input"
		show-word-limit
		type="text"
	/>

	<div class="fb-input-story-block__divider" />

	<fb-input
		v-model="textarea"
		maxlength="30"
		placeholder="Please input"
		show-word-limit
		type="textarea"
	/>
</div>`,
    }),
};
