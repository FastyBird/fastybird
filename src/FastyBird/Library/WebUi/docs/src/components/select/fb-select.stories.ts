import { onMounted, ref, watch } from "vue";

import { Meta, StoryObj } from "@storybook/vue3";
import {
    FbSelect,
    FbOption,
    FbOptionGroup,
    FbCheckbox,
    FbInput,
    FbIcon,
    FbButton,
    FbRow,
    FbCol,
    FbTag,
    CheckboxValueType,
} from "@fastybird/web-ui-components";
import { VariantTypes, ComponentSizeTypes } from "@fastybird/web-ui-constants";

import "./fb-select.stories.scss";

const meta: Meta<typeof FbSelect> = {
    component: FbSelect,
    title: "Components/Form/Select",
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbSelect>;

export const BasicUsage: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-select
		v-model="value"
		placeholder="Select"
		size="${ComponentSizeTypes.LARGE}"
	>
		<fb-option
			v-for="item in options"
			:key="item.value"
			:label="item.label"
			:value="item.value"
		/>
	</fb-select>

	<fb-select
		v-model="value"
		placeholder="Select"
	>
		<fb-option
			v-for="item in options"
			:key="item.value"
			:label="item.label"
			:value="item.value"
		/>
	</fb-select>

	<fb-select
		v-model="value"
		placeholder="Select"
		size="${ComponentSizeTypes.SMALL}"
	>
		<fb-option
			v-for="item in options"
			:key="item.value"
			:label="item.label"
			:value="item.value"
		/>
	</fb-select>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref('');

const options = [
	{
		value: 'Option1',
		label: 'Option1',
	},
	{
		value: 'Option2',
		label: 'Option2',
	},
	{
		value: 'Option3',
		label: 'Option3',
	},
	{
		value: 'Option4',
		label: 'Option4',
	},
	{
		value: 'Option5',
		label: 'Option5',
	},
];
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption },
        setup: () => {
            const value = ref("");

            const options = [
                {
                    value: "Option1",
                    label: "Option1",
                },
                {
                    value: "Option2",
                    label: "Option2",
                },
                {
                    value: "Option3",
                    label: "Option3",
                },
                {
                    value: "Option4",
                    label: "Option4",
                },
                {
                    value: "Option5",
                    label: "Option5",
                },
            ];

            return {
                value,
                options,
            };
        },
        template: `
<div class="fb-select-story-block">
	<div class="fb-select-story-block__form-row">
		<fb-select
			v-model="value"
			placeholder="Select"
			size="large"
		>
			<fb-option
				v-for="item in options"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			/>
		</fb-select>

		<fb-select v-model="value" placeholder="Select">
			<fb-option
				v-for="item in options"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			/>
		</fb-select>

		<fb-select
			v-model="value"
			placeholder="Select"
			size="small"
		>
			<fb-option
				v-for="item in options"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			/>
		</fb-select>
	</div>
</div>`,
    }),
};

export const DisabledOption: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-select
		v-model="value"
		placeholder="Select"
	>
		<fb-option
			v-for="item in options"
			:key="item.value"
			:label="item.label"
			:value="item.value"
			:disabled="item.disabled || false"
		/>
	</fb-select>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref('');

const options = [
	{
		value: 'Option1',
		label: 'Option1',
	},
	{
		value: 'Option2',
		label: 'Option2',
		disabled: true,
	},
	{
		value: 'Option3',
		label: 'Option3',
	},
	{
		value: 'Option4',
		label: 'Option4',
	},
	{
		value: 'Option5',
		label: 'Option5',
	},
];
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption },
        setup: () => {
            const value = ref("");

            const options = [
                {
                    value: "Option1",
                    label: "Option1",
                    disabled: false,
                },
                {
                    value: "Option2",
                    label: "Option2",
                    disabled: true,
                },
                {
                    value: "Option3",
                    label: "Option3",
                    disabled: false,
                },
                {
                    value: "Option4",
                    label: "Option4",
                    disabled: false,
                },
                {
                    value: "Option5",
                    label: "Option5",
                    disabled: false,
                },
            ];

            return {
                value,
                options,
            };
        },
        template: `
<div class="fb-select-story-block">
	<div class="fb-select-story-block__item">
		<fb-select
			v-model="value"
			placeholder="Select"
		>
			<fb-option
				v-for="item in options"
				:key="item.value"
				:label="item.label"
				:value="item.value"
				:disabled="item.disabled || false"
			/>
		</fb-select>
	</div>
</div>`,
    }),
};

export const DisabledSelect: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-select
		v-model="value"
		disabled
		placeholder="Select"
	>
		<fb-option
			v-for="item in options"
			:key="item.value"
			:label="item.label"
			:value="item.value"
		/>
	</fb-select>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref('');

const options = [
	{
		value: 'Option1',
		label: 'Option1',
	},
	{
		value: 'Option2',
		label: 'Option2',
	},
	{
		value: 'Option3',
		label: 'Option3',
	},
	{
		value: 'Option4',
		label: 'Option4',
	},
	{
		value: 'Option5',
		label: 'Option5',
	},
];
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption },
        setup: () => {
            const value = ref("");

            const options = [
                {
                    value: "Option1",
                    label: "Option1",
                },
                {
                    value: "Option2",
                    label: "Option2",
                },
                {
                    value: "Option3",
                    label: "Option3",
                },
                {
                    value: "Option4",
                    label: "Option4",
                },
                {
                    value: "Option5",
                    label: "Option5",
                },
            ];

            return {
                value,
                options,
            };
        },
        template: `
<div class="fb-select-story-block">
	<div class="fb-select-story-block__item">
		<fb-select
			v-model="value"
			disabled
			placeholder="Select"
		>
			<fb-option
				v-for="item in options"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			/>
		</fb-select>
	</div>
</div>`,
    }),
};

export const Clearable: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-select
		v-model="value"
		clearable
		placeholder="Select"
	>
		<fb-option
			v-for="item in options"
			:key="item.value"
			:label="item.label"
			:value="item.value"
		/>
	</fb-select>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref('');

const options = [
	{
		value: 'Option1',
		label: 'Option1',
	},
	{
		value: 'Option2',
		label: 'Option2',
	},
	{
		value: 'Option3',
		label: 'Option3',
	},
	{
		value: 'Option4',
		label: 'Option4',
	},
	{
		value: 'Option5',
		label: 'Option5',
	},
];
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption },
        setup: () => {
            const value = ref("");

            const options = [
                {
                    value: "Option1",
                    label: "Option1",
                },
                {
                    value: "Option2",
                    label: "Option2",
                },
                {
                    value: "Option3",
                    label: "Option3",
                },
                {
                    value: "Option4",
                    label: "Option4",
                },
                {
                    value: "Option5",
                    label: "Option5",
                },
            ];

            return {
                value,
                options,
            };
        },
        template: `
<div class="fb-select-story-block">
	<div class="fb-select-story-block__item">
		<fb-select
			v-model="value"
			clearable
			placeholder="Select"
		>
			<fb-option
				v-for="item in options"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			/>
		</fb-select>
	</div>
</div>`,
    }),
};

export const MultipleSelect: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="5">
			Default
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value1"
				multiple
				placeholder="Select"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-select>
		</fb-col>
	</fb-row>

	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="5">
			Use collapse-tags
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value2"
				multiple
				collapse-tags
				placeholder="Select"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-select>
		</fb-col>
	</fb-row>

	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="5">
			Use collapse-tags-tooltip
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value3"
				multiple
				collapse-tags
				collapse-tags-tooltip
				placeholder="Select"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-select>
		</fb-col>
	</fb-row>

	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="5">
			Use max-collapse-tags
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value4"
				multiple
				collapse-tags
				collapse-tags-tooltip
				:max-collapse-tags="3"
				placeholder="Select"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-select>
		</fb-col>
	</fb-row>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value1 = ref([]);
const value2 = ref([]);
const value3 = ref([]);
const value4 = ref([]);

const options = [
	{
		value: 'Option1',
		label: 'Option1',
	},
	{
		value: 'Option2',
		label: 'Option2',
	},
	{
		value: 'Option3',
		label: 'Option3',
	},
	{
		value: 'Option4',
		label: 'Option4',
	},
	{
		value: 'Option5',
		label: 'Option5',
	},
];
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption, FbRow, FbCol },
        setup: () => {
            const value1 = ref([]);
            const value2 = ref([]);
            const value3 = ref([]);
            const value4 = ref([]);

            const options = [
                {
                    value: "Option1",
                    label: "Option1",
                },
                {
                    value: "Option2",
                    label: "Option2",
                },
                {
                    value: "Option3",
                    label: "Option3",
                },
                {
                    value: "Option4",
                    label: "Option4",
                },
                {
                    value: "Option5",
                    label: "Option5",
                },
            ];

            return {
                value1,
                value2,
                value3,
                value4,
                options,
            };
        },
        template: `
<div class="fb-select-story-block">
	<fb-row :gutter="20" align="middle" class="fb-select-story-block__item">
		<fb-col :sm="12" :md="5">
			Default
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value1"
				multiple
				placeholder="Select"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-select>
		</fb-col>
	</fb-row>

	<fb-row :gutter="20" align="middle" class="fb-select-story-block__item">
		<fb-col :sm="12" :md="5">
			Use collapse-tags
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value2"
				multiple
				collapse-tags
				placeholder="Select"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-select>
		</fb-col>
	</fb-row>

	<fb-row :gutter="20" align="middle" class="fb-select-story-block__item">
		<fb-col :sm="12" :md="5">
			Use collapse-tags-tooltip
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value3"
				multiple
				collapse-tags
				collapse-tags-tooltip
				placeholder="Select"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-select>
		</fb-col>
	</fb-row>

	<fb-row :gutter="20" align="middle" class="fb-select-story-block__item">
		<fb-col :sm="12" :md="5">
			Use max-collapse-tags
		</fb-col>
		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value4"
				multiple
				collapse-tags
				collapse-tags-tooltip
				:max-collapse-tags="3"
				placeholder="Select"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-select>
		</fb-col>
	</fb-row>
</div>`,
    }),
};

export const CustomTemplate: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-select v-model="value" placeholder="Select">
		<fb-option
			v-for="item in cities"
			:key="item.value"
			:label="item.label"
			:value="item.value"
		>
			<span class="custom-label">
				{{ item.label }}
			</span>
			<span class="custom-description">
				{{ item.country }}
			</span>
		</fb-option>
	</fb-select>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref('');

const cities = [
	{
		value: 'Vienna',
		label: 'Vienna',
		country: 'Austria',
	},
	{
		value: 'Sofia',
		label: 'Sofia',
		country: 'Bulgaria',
	},
	{
		value: 'Helsinki',
		label: 'Helsinki',
		country: 'Finland',
	},
	{
		value: 'Rome',
		label: 'Rome',
		country: 'Italy',
	},
	{
		value: 'Monaco',
		label: 'Monaco',
		country: 'Monaco',
	},
	{
		value: 'Lisbon',
		label: 'Lisbon',
		country: 'Portugal',
	},
];
</script>

<style scoped>
.custom-label {
	float: left;
}

.custom-description {
	float: right;
	color: var(--fb-text-color-secondary);
	font-size: 13px;
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption },
        setup: () => {
            const value = ref("");

            const cities = [
                {
                    value: "Vienna",
                    label: "Vienna",
                    country: "Austria",
                },
                {
                    value: "Sofia",
                    label: "Sofia",
                    country: "Bulgaria",
                },
                {
                    value: "Helsinki",
                    label: "Helsinki",
                    country: "Finland",
                },
                {
                    value: "Rome",
                    label: "Rome",
                    country: "Italy",
                },
                {
                    value: "Monaco",
                    label: "Monaco",
                    country: "Monaco",
                },
                {
                    value: "Lisbon",
                    label: "Lisbon",
                    country: "Portugal",
                },
            ];

            return {
                value,
                cities,
            };
        },
        template: `
<div class="fb-select-story-block">
	<div class="fb-select-story-block__item">
		<fb-select v-model="value" placeholder="Select">
			<fb-option
				v-for="item in cities"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			>
				<span class="fb-select-story-block__custom-template-label">
					{{ item.label }}
				</span>
				<span class="fb-select-story-block__custom-template-description">
					{{ item.country }}
				</span>
			</fb-option>
		</fb-select>
	</div>
</div>`,
    }),
};

export const DropdownHeader: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-select
		v-model="value"
		multiple
		clearable
		collapse-tags
		placeholder="Select"
		popper-class="custom-header"
		:max-collapse-tags="1"
	>
		<template #header>
			<fb-checkbox
				v-model="checkAll"
				:indeterminate="indeterminate"
				@change="handleCheckAll"
			>
				All
			</fb-checkbox>
		</template>

		<fb-option
			v-for="item in cities"
			:key="item.value"
			:label="item.label"
			:value="item.value"
		/>
	</fb-select>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue';

import type { CheckboxValueType } from '@fastybird/web-ui-library';

const checkAll = ref(false);
const indeterminate = ref(false);
const value = ref<CheckboxValueType[]>([]);

const cities = ref([
	{
		value: 'Vienna',
		label: 'Vienna',
	},
	{
		value: 'Sofia',
		label: 'Sofia',
	},
	{
		value: 'Helsinki',
		label: 'Helsinki',
	},
	{
		value: 'Rome',
		label: 'Rome',
	},
	{
		value: 'Monaco',
		label: 'Monaco',
	},
	{
		value: 'Lisbon',
		label: 'Lisbon',
	},
]);

watch(value, (val) => {
	if (val.length === 0) {
		checkAll.value = false
		indeterminate.value = false
	} else if (val.length === cities.value.length) {
		checkAll.value = true
		indeterminate.value = false
	} else {
		indeterminate.value = true
	}
})

const handleCheckAll = (val: CheckboxValueType) => {
	indeterminate.value = false;

	if (val) {
		value.value = cities.value.map((_) => _.value);
	} else {
		value.value = [];
	}
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption, FbCheckbox },
        setup: () => {
            const checkAll = ref(false);
            const indeterminate = ref(false);
            const value = ref<CheckboxValueType[]>([]);

            const cities = ref([
                {
                    value: "Vienna",
                    label: "Vienna",
                },
                {
                    value: "Sofia",
                    label: "Sofia",
                },
                {
                    value: "Helsinki",
                    label: "Helsinki",
                },
                {
                    value: "Rome",
                    label: "Rome",
                },
                {
                    value: "Monaco",
                    label: "Monaco",
                },
                {
                    value: "Lisbon",
                    label: "Lisbon",
                },
            ]);

            watch(value, (val) => {
                if (val.length === 0) {
                    checkAll.value = false;
                    indeterminate.value = false;
                } else if (val.length === cities.value.length) {
                    checkAll.value = true;
                    indeterminate.value = false;
                } else {
                    indeterminate.value = true;
                }
            });

            const handleCheckAll = (val: CheckboxValueType) => {
                indeterminate.value = false;
                if (val) {
                    value.value = cities.value.map((_) => _.value);
                } else {
                    value.value = [];
                }
            };

            return {
                checkAll,
                indeterminate,
                value,
                cities,
                handleCheckAll,
            };
        },
        template: `
<div class="fb-select-story-block">
	<div class="fb-select-story-block__item">
		<fb-select
			v-model="value"
			multiple
			clearable
			collapse-tags
			placeholder="Select"
			popper-class="custom-header"
			:max-collapse-tags="1"
		>
			<template #header>
				<fb-checkbox
					v-model="checkAll"
					:indeterminate="indeterminate"
					@change="handleCheckAll"
				>
					All
				</fb-checkbox>
			</template>

			<fb-option
				v-for="item in cities"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			/>
		</fb-select>
	</div>
</div>`,
    }),
};

export const DropdownFooter: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-select v-model="value" placeholder="Select">
		<fb-option
			v-for="item in cities"
			:key="item.value"
			:label="item.label"
			:value="item.value"
		/>
		<template #footer>
			<fb-button v-if="!isAdding" text bg size="${ComponentSizeTypes.SMALL}" @click="onAddOption">
				Add an option
			</fb-button>

			<template v-else>
				<fb-input
					v-model="optionName"
					class="option-input"
					placeholder="Your custom value"
					size="${ComponentSizeTypes.SMALL}"
				/>
				<fb-button variant="${VariantTypes.PRIMARY}" size="${ComponentSizeTypes.SMALL}" @click="onConfirm">
					Confirm
				</fb-button>
				<fb-button size="${ComponentSizeTypes.SMALL}" @click="clear">
					Cancel
				</fb-button>
			</template>
		</template>
	</fb-select>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

import type { CheckboxValueType } from '@fastybird/web-ui-library';

const isAdding = ref(false);
const value = ref<CheckboxValueType[]>([]);
const optionName = ref('');

const cities = ref([
	{
		value: 'Vienna',
		label: 'Vienna',
	},
	{
		value: 'Sofia',
		label: 'Sofia',
	},
	{
		value: 'Helsinki',
		label: 'Helsinki',
	},
	{
		value: 'Rome',
		label: 'Rome',
	},
	{
		value: 'Monaco',
		label: 'Monaco',
	},
	{
		value: 'Lisbon',
		label: 'Lisbon',
	},
]);

const onAddOption = () => {
	isAdding.value = true;
}

const onConfirm = () => {
	if (optionName.value) {
		cities.value.push({
			label: optionName.value,
			value: optionName.value,
		});

		clear();
	}
}

const clear = () => {
	optionName.value = '';
	isAdding.value = false;
}
</script>

<style lang="scss" scoped>
.option-input {
	width: 100%;
	margin-bottom: 8px;
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption, FbButton, FbInput },
        setup: () => {
            const isAdding = ref(false);
            const value = ref<CheckboxValueType[]>([]);
            const optionName = ref("");

            const cities = ref([
                {
                    value: "Vienna",
                    label: "Vienna",
                },
                {
                    value: "Sofia",
                    label: "Sofia",
                },
                {
                    value: "Helsinki",
                    label: "Helsinki",
                },
                {
                    value: "Rome",
                    label: "Rome",
                },
                {
                    value: "Monaco",
                    label: "Monaco",
                },
                {
                    value: "Lisbon",
                    label: "Lisbon",
                },
            ]);

            const onAddOption = () => {
                isAdding.value = true;
            };

            const onConfirm = () => {
                if (optionName.value) {
                    cities.value.push({
                        label: optionName.value,
                        value: optionName.value,
                    });
                    clear();
                }
            };

            const clear = () => {
                optionName.value = "";
                isAdding.value = false;
            };

            return {
                isAdding,
                value,
                optionName,
                cities,
                onAddOption,
                onConfirm,
                clear,
            };
        },
        template: `
<div class="fb-select-story-block">
	<div class="fb-select-story-block__item">
		<fb-select v-model="value" placeholder="Select">
			<fb-option
				v-for="item in cities"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			/>
			<template #footer>
				<fb-button v-if="!isAdding" text bg size="${ComponentSizeTypes.SMALL}" @click="onAddOption">
					Add an option
				</fb-button>

				<template v-else>
					<fb-input
						v-model="optionName"
						class="fb-select-story-block__custom-footer-input"
						placeholder="Your custom value"
						size="${ComponentSizeTypes.SMALL}"
					/>
					<fb-button variant="${VariantTypes.PRIMARY}" size="${ComponentSizeTypes.SMALL}" @click="onConfirm">
						Confirm
					</fb-button>
					<fb-button size="${ComponentSizeTypes.SMALL}" @click="clear">
						Cancel
					</fb-button>
				</template>
			</template>
		</fb-select>
	</div>
</div>`,
    }),
};

export const Grouping: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-select v-model="value" placeholder="Select">
		<fb-option-group
			v-for="group in options"
			:key="group.label"
			:label="group.label"
		>
			<fb-option
				v-for="item in group.options"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			/>
		</fb-option-group>
	</fb-select>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref('');

const options = [
	{
		label: 'Popular cities',
		options: [
			{
				value: 'Rome',
				label: 'Rome',
			},
			{
				value: 'Vienna',
				label: 'Vienna',
			},
		],
	},
	{
		label: 'City name',
		options: [
			{
				value: 'Sofia',
				label: 'Sofia',
			},
			{
				value: 'Helsinki',
				label: 'Helsinki',
			},
			{
				value: 'Monaco',
				label: 'Monaco',
			},
			{
				value: 'Lisbon',
				label: 'Lisbon',
			},
		],
	},
];
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOptionGroup, FbOption },
        setup: () => {
            const value = ref("");

            const options = [
                {
                    label: "Popular cities",
                    options: [
                        {
                            value: "Rome",
                            label: "Rome",
                        },
                        {
                            value: "Vienna",
                            label: "Vienna",
                        },
                    ],
                },
                {
                    label: "City name",
                    options: [
                        {
                            value: "Sofia",
                            label: "Sofia",
                        },
                        {
                            value: "Helsinki",
                            label: "Helsinki",
                        },
                        {
                            value: "Monaco",
                            label: "Monaco",
                        },
                        {
                            value: "Lisbon",
                            label: "Lisbon",
                        },
                    ],
                },
            ];

            return {
                value,
                options,
            };
        },
        template: `
<div class="fb-select-story-block">
	<div class="fb-select-story-block__item">
		<fb-select v-model="value" placeholder="Select">
			<fb-option-group
				v-for="group in options"
				:key="group.label"
				:label="group.label"
			>
				<fb-option
					v-for="item in group.options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-option-group>
		</fb-select>
	</div>
</div>`,
    }),
};

export const OptionFilter: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-select
		v-model="value"
		filterable
		placeholder="Select"
	>
		<fb-option
			v-for="item in options"
			:key="item.value"
			:label="item.label"
			:value="item.value"
		/>
	</fb-select>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref('');

const options = [
	{
		value: 'Vienna',
		label: 'Vienna',
	},
	{
		value: 'Sofia',
		label: 'Sofia',
	},
	{
		value: 'Helsinki',
		label: 'Helsinki',
	},
	{
		value: 'Rome',
		label: 'Rome',
	},
	{
		value: 'Monaco',
		label: 'Monaco',
	},
	{
		value: 'Lisbon',
		label: 'Lisbon',
	},
];
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption },
        setup: () => {
            const value = ref("");

            const options = [
                {
                    value: "Vienna",
                    label: "Vienna",
                },
                {
                    value: "Sofia",
                    label: "Sofia",
                },
                {
                    value: "Helsinki",
                    label: "Helsinki",
                },
                {
                    value: "Rome",
                    label: "Rome",
                },
                {
                    value: "Monaco",
                    label: "Monaco",
                },
                {
                    value: "Lisbon",
                    label: "Lisbon",
                },
            ];

            return {
                value,
                options,
            };
        },
        template: `
<div class="fb-select-story-block">
	<div class="fb-select-story-block__item">
		<fb-select
			v-model="value"
			filterable
			placeholder="Select"
		>
			<fb-option
				v-for="item in options"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			/>
		</fb-select>
	</div>
</div>`,
    }),
};

export const RemoteSearch: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-row :gutter="20" align="middle" class="fb-select-story-block__item">
		<fb-col :sm="12" :md="5">
			Default
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value"
				multiple
				filterable
				remote
				reserve-keyword
				placeholder="Please enter a keyword"
				:remote-method="remoteMethod"
				:loading="loading"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-select>
		</fb-col>
	</fb-row>

	<fb-row :gutter="20" align="middle" class="fb-select-story-block__item">
		<fb-col :sm="12" :md="5">
			Use remote-show-suffix
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value"
				multiple
				filterable
				remote
				reserve-keyword
				placeholder="Please enter a keyword"
				remote-show-suffix
				:remote-method="remoteMethod"
				:loading="loading"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-select>
		</fb-col>
	</fb-row>
</template>

<script lang="ts" setup>
import { ref, onMounted } from 'vue';

interface ListItem {
  value: string;
  label: string;
}

const list = ref<ListItem[]>([]);
const options = ref<ListItem[]>([]);
const value = ref<string[]>([]);
const loading = ref(false);

onMounted((): void => {
	list.value = states.map((item) => {
		return { value: \`value:\$\{item\}\`, label: item }
	});
})

const remoteMethod = (query: string): void => {
	if (query) {
		loading.value = true;

		setTimeout(() => {
			loading.value = false
			options.value = list.value.filter((item) => {
				return item.label.toLowerCase().includes(query.toLowerCase())
			})
		}, 200);
	} else {
		options.value = [];
	}
}

const states = [
	'Alabama',
	'Alaska',
	'Arizona',
	'Arkansas',
	'California',
	'Colorado',
	'Connecticut',
	'Delaware',
	'Florida',
	'Georgia',
	'Hawaii',
	'Idaho',
	'Illinois',
	'Indiana',
	'Iowa',
	'Kansas',
	'Kentucky',
	'Louisiana',
	'Maine',
	'Maryland',
	'Massachusetts',
	'Michigan',
	'Minnesota',
	'Mississippi',
	'Missouri',
	'Montana',
	'Nebraska',
	'Nevada',
	'New Hampshire',
	'New Jersey',
	'New Mexico',
	'New York',
	'North Carolina',
	'North Dakota',
	'Ohio',
	'Oklahoma',
	'Oregon',
	'Pennsylvania',
	'Rhode Island',
	'South Carolina',
	'South Dakota',
	'Tennessee',
	'Texas',
	'Utah',
	'Vermont',
	'Virginia',
	'Washington',
	'West Virginia',
	'Wisconsin',
	'Wyoming',
];
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption, FbRow, FbCol },
        setup: () => {
            interface ListItem {
                value: string;
                label: string;
            }

            const list = ref<ListItem[]>([]);
            const options = ref<ListItem[]>([]);
            const value = ref<string[]>([]);
            const loading = ref(false);

            onMounted(() => {
                list.value = states.map((item) => {
                    return { value: `value:${item}`, label: item };
                });
            });

            const remoteMethod = (query: string) => {
                if (query) {
                    loading.value = true;
                    setTimeout(() => {
                        loading.value = false;
                        options.value = list.value.filter((item) => {
                            return item.label.toLowerCase().includes(query.toLowerCase());
                        });
                    }, 200);
                } else {
                    options.value = [];
                }
            };

            const states = [
                "Alabama",
                "Alaska",
                "Arizona",
                "Arkansas",
                "California",
                "Colorado",
                "Connecticut",
                "Delaware",
                "Florida",
                "Georgia",
                "Hawaii",
                "Idaho",
                "Illinois",
                "Indiana",
                "Iowa",
                "Kansas",
                "Kentucky",
                "Louisiana",
                "Maine",
                "Maryland",
                "Massachusetts",
                "Michigan",
                "Minnesota",
                "Mississippi",
                "Missouri",
                "Montana",
                "Nebraska",
                "Nevada",
                "New Hampshire",
                "New Jersey",
                "New Mexico",
                "New York",
                "North Carolina",
                "North Dakota",
                "Ohio",
                "Oklahoma",
                "Oregon",
                "Pennsylvania",
                "Rhode Island",
                "South Carolina",
                "South Dakota",
                "Tennessee",
                "Texas",
                "Utah",
                "Vermont",
                "Virginia",
                "Washington",
                "West Virginia",
                "Wisconsin",
                "Wyoming",
            ];

            return {
                list,
                options,
                value,
                loading,
                states,
                remoteMethod,
            };
        },
        template: `
<div class="fb-select-story-block">
	<fb-row :gutter="20" align="middle" class="fb-select-story-block__item">
		<fb-col :sm="12" :md="5">
			Default
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value"
				multiple
				filterable
				remote
				reserve-keyword
				placeholder="Please enter a keyword"
				:remote-method="remoteMethod"
				:loading="loading"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-select>
		</fb-col>
	</fb-row>

	<fb-row :gutter="20" align="middle" class="fb-select-story-block__item">
		<fb-col :sm="12" :md="5">
			Use remote-show-suffix
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value"
				multiple
				filterable
				remote
				reserve-keyword
				placeholder="Please enter a keyword"
				remote-show-suffix
				:remote-method="remoteMethod"
				:loading="loading"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
			</fb-select>
		</fb-col>
	</fb-row>
</div>`,
    }),
};

export const CreateNewItems: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-select
		v-model="value"
		multiple
		filterable
		allow-create
		default-first-option
		:reserve-keyword="false"
		placeholder="Choose tags for your article"
	>
		<fb-option
			v-for="item in options"
			:key="item.value"
			:label="item.label"
			:value="item.value"
		/>
	</fb-select>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref<string[]>([]);

const options = [
	{
		value: 'HTML',
		label: 'HTML',
	},
	{
		value: 'CSS',
		label: 'CSS',
	},
	{
		value: 'JavaScript',
		label: 'JavaScript',
	},
];
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption },
        setup: () => {
            const value = ref<string[]>([]);

            const options = [
                {
                    value: "HTML",
                    label: "HTML",
                },
                {
                    value: "CSS",
                    label: "CSS",
                },
                {
                    value: "JavaScript",
                    label: "JavaScript",
                },
            ];

            return {
                value,
                options,
            };
        },
        template: `
<div class="fb-select-story-block">
	<div class="fb-select-story-block__item">
		<fb-select
			v-model="value"
			multiple
			filterable
			allow-create
			default-first-option
			:reserve-keyword="false"
			placeholder="Choose tags for your article"
		>
			<fb-option
				v-for="item in options"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			/>
		</fb-select>
	</div>
</div>`,
    }),
};

export const ValueKeyAttribute: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-select
		v-model="value"
		value-key="id"
		placeholder="Select"
	>
		<fb-option
			v-for="item in options"
			:key="item.id"
			:label="item.label"
			:value="item"
		/>
	</fb-select>

	<p>
		Selected option's description:
		<strong>{{ value ? value.desc : 'nothing selected' }}</strong>
	</p>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

type Option = {
	id: number;
	label: string;
	desc: string;
};

const value = ref<Option>();

const options = ref([
	{ id: 1, label: 'Option A', desc: 'Option A - 230506' },
	{ id: 2, label: 'Option B', desc: 'Option B - 230506' },
	{ id: 3, label: 'Option C', desc: 'Option C - 230506' },
	{ id: 4, label: 'Option A', desc: 'Option A - 230507' },
]);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption },
        setup: () => {
            type Option = {
                id: number;
                label: string;
                desc: string;
            };

            const value = ref<Option>();

            const options = ref([
                { id: 1, label: "Option A", desc: "Option A - 230506" },
                { id: 2, label: "Option B", desc: "Option B - 230506" },
                { id: 3, label: "Option C", desc: "Option C - 230506" },
                { id: 4, label: "Option A", desc: "Option A - 230507" },
            ]);

            return {
                value,
                options,
            };
        },
        template: `
<div class="fb-select-story-block">
	<div class="fb-select-story-block__item">
		<fb-select
			v-model="value"
			value-key="id"
			placeholder="Select"
		>
			<fb-option
				v-for="item in options"
				:key="item.id"
				:label="item.label"
				:value="item"
			/>
		</fb-select>
	</div>

	<p>
		Selected option's description:
		<strong>{{ value ? value.desc : 'nothing selected' }}</strong>
	</p>
</div>`,
    }),
};

export const CustomTag: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-select
		v-model="value"
		multiple
		placeholder="Select"
	>
		<fb-option
			v-for="item in colors"
			:key="item.value"
			:label="item.label"
			:value="item.value"
		>
			<fb-tag :color="item.value" size="${ComponentSizeTypes.SMALL}" />
			<span :style="{ color: item.value }">{{ item.label }}</span>
		</fb-option>
		<template #tag>
			<fb-tag v-for="color in value" :key="color.value" :color="color" />
		</template>
	</fb-select>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const value = ref([]);

const colors = [
	{
		value: '#E63415',
		label: 'red',
	},
	{
		value: '#FF6600',
		label: 'orange',
	},
	{
		value: '#FFDE0A',
		label: 'yellow',
	},
	{
		value: '#1EC79D',
		label: 'green',
	},
	{
		value: '#14CCCC',
		label: 'cyan',
	},
	{
		value: '#4167F0',
		label: 'blue',
	},
	{
		value: '#6222C9',
		label: 'purple',
	},
];

colors.forEach((color) => {
	value.value.push(color.value);
});
</script>

<style scoped>
.fb-tag {
	border: none;
	aspect-ratio: 1;
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption, FbTag },
        setup: () => {
            const value = ref([]);

            const colors = [
                {
                    value: "#E63415",
                    label: "red",
                },
                {
                    value: "#FF6600",
                    label: "orange",
                },
                {
                    value: "#FFDE0A",
                    label: "yellow",
                },
                {
                    value: "#1EC79D",
                    label: "green",
                },
                {
                    value: "#14CCCC",
                    label: "cyan",
                },
                {
                    value: "#4167F0",
                    label: "blue",
                },
                {
                    value: "#6222C9",
                    label: "purple",
                },
            ];

            colors.forEach((color) => {
                value.value.push(color.value);
            });

            return {
                value,
                colors,
            };
        },
        template: `
<div class="fb-select-story-block">
	<div class="fb-select-story-block__item">
		<fb-select
			v-model="value"
			multiple
			placeholder="Select"
		>
			<fb-option
				v-for="item in colors"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			>
				<fb-tag :color="item.value" class="fb-select-story-block__custom-tag-option" size="${ComponentSizeTypes.SMALL}" />
				<span :style="{ color: item.value }">{{ item.label }}</span>
			</fb-option>
			<template #tag>
				<fb-tag v-for="color in value" :key="color.value" :color="color" class="fb-select-story-block__custom-tag-list" />
			</template>
		</fb-select>
	</div>
</div>`,
    }),
};

export const CustomLoading: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="5">
			Loading icon 1
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value"
				multiple
				filterable
				remote
				reserve-keyword
				placeholder="Please enter a keyword"
				:remote-method="remoteMethod"
				:loading="loading"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
				<template #loading>
					<svg class="circular" viewBox="0 0 50 50">
						<circle class="path" cx="25" cy="25" r="20" fill="none" />
					</svg>
				</template>
			</fb-select>
		</fb-col>
	</fb-row>

	<fb-row :gutter="20" align="middle">
		<fb-col :sm="12" :md="5">
			Loading icon 2
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value"
				multiple
				filterable
				remote
				reserve-keyword
				placeholder="Please enter a keyword"
				:remote-method="remoteMethod"
				:loading="loading"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
				<template #loading>
					<fb-icon class="is-loading">
						<svg class="circular" viewBox="0 0 20 20">
							<g
								stroke-width="0"
								class="group"
							>
								<circle r="3.375" class="dot1" rx="0" ry="0" />
								<circle r="3.375" class="dot2" rx="0" ry="0" />
								<circle r="3.375" class="dot4" rx="0" ry="0" />
								<circle r="3.375" class="dot3" rx="0" ry="0" />
							</g>
						</svg>
					</fb-icon>
				</template>
			</fb-select>
		</fb-col>
	</fb-row>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

interface ListItem {
	value: string;
	label: string;
};

const list = ref<ListItem[]>([]);
const options = ref<ListItem[]>([]);
const value = ref<string[]>([]);
const loading = ref(false);

onMounted(() => {
	list.value = states.map((item) => {
		return { value: \`value:\$\{item\}\`, label: item }
	});
});

const remoteMethod = (query: string): void => {
	if (query) {
		loading.value = true;

		setTimeout(() => {
			loading.value = false
			options.value = list.value.filter((item) => {
				return item.label.toLowerCase().includes(query.toLowerCase())
			})
		}, 3000);
	} else {
		options.value = [];
	}
}

const states = [
	'Alabama',
	'Alaska',
	'Arizona',
	'Arkansas',
	'California',
	'Colorado',
	'Connecticut',
	'Delaware',
	'Florida',
	'Georgia',
	'Hawaii',
	'Idaho',
	'Illinois',
	'Indiana',
	'Iowa',
	'Kansas',
	'Kentucky',
	'Louisiana',
	'Maine',
	'Maryland',
	'Massachusetts',
	'Michigan',
	'Minnesota',
	'Mississippi',
	'Missouri',
	'Montana',
	'Nebraska',
	'Nevada',
	'New Hampshire',
	'New Jersey',
	'New Mexico',
	'New York',
	'North Carolina',
	'North Dakota',
	'Ohio',
	'Oklahoma',
	'Oregon',
	'Pennsylvania',
	'Rhode Island',
	'South Carolina',
	'South Dakota',
	'Tennessee',
	'Texas',
	'Utah',
	'Vermont',
	'Virginia',
	'Washington',
	'West Virginia',
	'Wisconsin',
	'Wyoming',
];
</script>

<style>
.circular {
  display: inline;
  height: 30px;
  width: 30px;
  animation: loading-rotate 2s linear infinite;
}
.circular .path {
  animation: loading-dash 1.5s ease-in-out infinite;
  stroke-dasharray: 90, 150;
  stroke-dashoffset: 0;
  stroke-width: 2;
  stroke: var(--fb-color-primary);
  stroke-linecap: round;
}
.loading-path group {
  animation: none;
  stroke: none;
}
.loading-path .dot1 {
  transform: translate(3.75px, 3.75px);
  fill: var(--fb-color-primary);
  animation: custom-spin-move 1s infinite linear alternate;
  opacity: 0.3;
}
.loading-path .dot2 {
  transform: translate(calc(100% - 3.75px), 3.75px);
  fill: var(--fb-color-primary);
  animation: custom-spin-move 1s infinite linear alternate;
  opacity: 0.3;
  animation-delay: 0.4s;
}
.loading-path .dot3 {
  transform: translate(3.75px, calc(100% - 3.75px));
  fill: var(--fb-color-primary);
  animation: custom-spin-move 1s infinite linear alternate;
  opacity: 0.3;
  animation-delay: 1.2s;
}
.loading-path .dot4 {
  transform: translate(calc(100% - 3.75px), calc(100% - 3.75px));
  fill: var(--fb-color-primary);
  animation: custom-spin-move 1s infinite linear alternate;
  opacity: 0.3;
  animation-delay: 0.8s;
}
@keyframes loading-rotate {
  to {
    transform: rotate(360deg);
  }
}
@keyframes loading-dash {
  0% {
    stroke-dasharray: 1, 200;
    stroke-dashoffset: 0;
  }
  50% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -40px;
  }
  100% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -120px;
  }
}
@keyframes custom-spin-move {
  to {
    opacity: 1;
  }
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbSelect, FbOption, FbIcon, FbRow, FbCol },
        setup: () => {
            interface ListItem {
                value: string;
                label: string;
            }

            const list = ref<ListItem[]>([]);
            const options = ref<ListItem[]>([]);
            const value = ref<string[]>([]);
            const loading = ref(false);

            onMounted(() => {
                list.value = states.map((item) => {
                    return { value: `value:${item}`, label: item };
                });
            });

            const remoteMethod = (query: string): void => {
                if (query) {
                    loading.value = true;

                    setTimeout(() => {
                        loading.value = false;
                        options.value = list.value.filter((item) => {
                            return item.label.toLowerCase().includes(query.toLowerCase());
                        });
                    }, 3000);
                } else {
                    options.value = [];
                }
            };

            const states = [
                "Alabama",
                "Alaska",
                "Arizona",
                "Arkansas",
                "California",
                "Colorado",
                "Connecticut",
                "Delaware",
                "Florida",
                "Georgia",
                "Hawaii",
                "Idaho",
                "Illinois",
                "Indiana",
                "Iowa",
                "Kansas",
                "Kentucky",
                "Louisiana",
                "Maine",
                "Maryland",
                "Massachusetts",
                "Michigan",
                "Minnesota",
                "Mississippi",
                "Missouri",
                "Montana",
                "Nebraska",
                "Nevada",
                "New Hampshire",
                "New Jersey",
                "New Mexico",
                "New York",
                "North Carolina",
                "North Dakota",
                "Ohio",
                "Oklahoma",
                "Oregon",
                "Pennsylvania",
                "Rhode Island",
                "South Carolina",
                "South Dakota",
                "Tennessee",
                "Texas",
                "Utah",
                "Vermont",
                "Virginia",
                "Washington",
                "West Virginia",
                "Wisconsin",
                "Wyoming",
            ];

            return {
                list,
                options,
                value,
                loading,
                remoteMethod,
                states,
            };
        },
        template: `
<div class="fb-select-story-block">
	<fb-row :gutter="20" align="middle" class="fb-select-story-block__item">
		<fb-col :sm="12" :md="5">
			Loading icon 1
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value"
				multiple
				filterable
				remote
				reserve-keyword
				placeholder="Please enter a keyword"
				:remote-method="remoteMethod"
				:loading="loading"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
				<template #loading>
					<svg class="fb-select-story-block__circular-loader-icon" viewBox="0 0 50 50">
						<circle class="fb-select-story-block__circular-loader-icon-path" cx="25" cy="25" r="20" fill="none" />
					</svg>
				</template>
			</fb-select>
		</fb-col>
	</fb-row>

	<fb-row :gutter="20" align="middle" class="fb-select-story-block__item">
		<fb-col :sm="12" :md="5">
			Loading icon 2
		</fb-col>

		<fb-col :sm="12" :md="13">
			<fb-select
				v-model="value"
				multiple
				filterable
				remote
				reserve-keyword
				placeholder="Please enter a keyword"
				:remote-method="remoteMethod"
				:loading="loading"
			>
				<fb-option
					v-for="item in options"
					:key="item.value"
					:label="item.label"
					:value="item.value"
				/>
				<template #loading>
					<fb-icon class="fb-select-story-block__dot-loader">
						<svg class="fb-select-story-block__circular-loader-icon" viewBox="0 0 20 20">
							<g
								stroke-width="0"
								class="fb-select-story-block__dot-loader-path"
							>
								<circle r="3.375" class="dot1" rx="0" ry="0" />
								<circle r="3.375" class="dot2" rx="0" ry="0" />
								<circle r="3.375" class="dot4" rx="0" ry="0" />
								<circle r="3.375" class="dot3" rx="0" ry="0" />
							</g>
						</svg>
					</fb-icon>
				</template>
			</fb-select>
		</fb-col>
	</fb-row>
</div>`,
    }),
};
