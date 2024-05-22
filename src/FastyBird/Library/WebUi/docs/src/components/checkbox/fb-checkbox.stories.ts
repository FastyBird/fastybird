import { ref } from "vue";

import { Meta, StoryObj } from "@storybook/vue3";
import { FbCheckbox, FbCheckboxGroup, FbCheckboxButton } from "@fastybird/web-ui-components";
import { ComponentSizeTypes } from "@fastybird/web-ui-constants";

import "./fb-checkbox.stories.scss";

const meta: Meta<typeof FbCheckbox> = {
    component: FbCheckbox,
    title: "Components/Form/Checkbox",
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbCheckbox>;

export const BasicUsage: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<div>
		<fb-checkbox v-model="checked1" label="Option 1" size="${ComponentSizeTypes.LARGE}" />
		<fb-checkbox v-model="checked2" label="Option 2" size="${ComponentSizeTypes.LARGE}" />
	</div>
	<div>
		<fb-checkbox v-model="checked3" label="Option 1" />
		<fb-checkbox v-model="checked4" label="Option 2" />
	</div>
	<div>
		<fb-checkbox v-model="checked5" label="Option 1" size="${ComponentSizeTypes.SMALL}" />
		<fb-checkbox v-model="checked6" label="Option 2" size="${ComponentSizeTypes.SMALL}" />
	</div>
	<div>
		<fb-checkbox v-model="checked7" label="Option 1" size="${ComponentSizeTypes.SMALL}" disabled />
		<fb-checkbox v-model="checked8" label="Option 2" size="${ComponentSizeTypes.SMALL}" disabled />
	</div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const checked1 = ref(true);
const checked2 = ref(false);
const checked3 = ref(false);
const checked4 = ref(false);
const checked5 = ref(false);
const checked6 = ref(false);
const checked7 = ref(false);
const checked8 = ref(false);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbCheckbox },
        setup: () => {
            const checked1 = ref(true);
            const checked2 = ref(false);
            const checked3 = ref(false);
            const checked4 = ref(false);
            const checked5 = ref(false);
            const checked6 = ref(false);
            const checked7 = ref(false);
            const checked8 = ref(false);

            return {
                checked1,
                checked2,
                checked3,
                checked4,
                checked5,
                checked6,
                checked7,
                checked8,
            };
        },
        template: `
<div class="fb-checkbox-story-block">
	<div class="fb-checkbox-story-block__item">
		<fb-checkbox v-model="checked1" label="Option 1" size="${ComponentSizeTypes.LARGE}" />
		<fb-checkbox v-model="checked2" label="Option 2" size="${ComponentSizeTypes.LARGE}" />
	</div>
	<div class="fb-checkbox-story-block__item">
		<fb-checkbox v-model="checked3" label="Option 1" />
		<fb-checkbox v-model="checked4" label="Option 2" />
	</div>
	<div class="fb-checkbox-story-block__item">
		<fb-checkbox v-model="checked5" label="Option 1" size="${ComponentSizeTypes.SMALL}" />
		<fb-checkbox v-model="checked6" label="Option 2" size="${ComponentSizeTypes.SMALL}" />
	</div>
	<div class="fb-checkbox-story-block__item">
		<fb-checkbox v-model="checked7" label="Option 1" size="${ComponentSizeTypes.SMALL}" disabled />
		<fb-checkbox v-model="checked8" label="Option 2" size="${ComponentSizeTypes.SMALL}" disabled />
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
	<fb-checkbox v-model="checked1" disabled>Disabled</fb-checkbox>
	<fb-checkbox v-model="checked2">Not disabled</fb-checkbox>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const checked1 = ref(true);
const checked2 = ref(false);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbCheckbox },
        setup: () => {
            const checked1 = ref(true);
            const checked2 = ref(false);

            return {
                checked1,
                checked2,
            };
        },
        template: `
<div class="fb-checkbox-story-block">
	<fb-checkbox v-model="checked1" disabled>Disabled</fb-checkbox>
	<fb-checkbox v-model="checked2">Not disabled</fb-checkbox>
</div>`,
    }),
};

export const CheckboxGroup: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-checkbox-group v-model="checkList">
		<fb-checkbox label="Option A" value="Value A" />
		<fb-checkbox label="Option B" value="Value B" />
		<fb-checkbox label="Option C" value="Value C" />
		<fb-checkbox label="disabled" value="Value disabled" disabled />
		<fb-checkbox
			label="selected and disabled"
			value="Value selected and disabled"
			disabled
		/>
	</fb-checkbox-group>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const checkList = ref(['Value selected and disabled', 'Value A']);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbCheckbox, FbCheckboxGroup },
        setup: () => {
            const checkList = ref(["Value selected and disabled", "Value A"]);

            return {
                checkList,
            };
        },
        template: `
<div class="fb-checkbox-story-block">
	<fb-checkbox-group v-model="checkList">
		<fb-checkbox label="Option A" value="Value A" />
		<fb-checkbox label="Option B" value="Value B" />
		<fb-checkbox label="Option C" value="Value C" />
		<fb-checkbox label="disabled" value="Value disabled" disabled />
		<fb-checkbox
			label="selected and disabled"
			value="Value selected and disabled"
			disabled
		/>
	</fb-checkbox-group>
</div>`,
    }),
};

export const Indeterminate: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-checkbox
		v-model="checkAll"
		:indeterminate="isIndeterminate"
		@change="handleCheckAllChange"
	>
		Check all
	</fb-checkbox>
	<fb-checkbox-group
		v-model="checkedCities"
		@change="handleCheckedCitiesChange"
	>
		<fb-checkbox
			v-for="city in cities"
			:key="city"
			:label="city"
			:value="city"
		>
			{{ city }}
		</fb-checkbox>
	</fb-checkbox-group>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const checkAll = ref(false);
const isIndeterminate = ref(true);
const checkedCities = ref(['New York', 'Washington']);
const cities = ['New York', 'Washington', 'Los Angeles', 'Chicago'];

const handleCheckAllChange = (val: boolean) => {
	checkedCities.value = val ? cities : [];
	isIndeterminate.value = false;
};

const handleCheckedCitiesChange = (value: string[]) => {
	const checkedCount = value.length;
	checkAll.value = checkedCount === cities.length;
	isIndeterminate.value = checkedCount > 0 && checkedCount < cities.length;
};
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbCheckbox, FbCheckboxGroup },
        setup: () => {
            const checkAll = ref(false);
            const isIndeterminate = ref(true);
            const checkedCities = ref(["New York", "Washington"]);
            const cities = ["New York", "Washington", "Los Angeles", "Chicago"];

            const handleCheckAllChange = (val: boolean) => {
                checkedCities.value = val ? cities : [];
                isIndeterminate.value = false;
            };

            const handleCheckedCitiesChange = (value: string[]) => {
                const checkedCount = value.length;
                checkAll.value = checkedCount === cities.length;
                isIndeterminate.value = checkedCount > 0 && checkedCount < cities.length;
            };

            return {
                checkAll,
                isIndeterminate,
                checkedCities,
                cities,
                handleCheckAllChange,
                handleCheckedCitiesChange,
            };
        },
        template: `
<div class="fb-checkbox-story-block">
	<fb-checkbox
		v-model="checkAll"
		:indeterminate="isIndeterminate"
		@change="handleCheckAllChange"
	>
		Check all
	</fb-checkbox>
	<fb-checkbox-group
		v-model="checkedCities"
		@change="handleCheckedCitiesChange"
	>
		<fb-checkbox
			v-for="city in cities"
			:key="city"
			:label="city"
			:value="city"
		>
			{{ city }}
		</fb-checkbox>
	</fb-checkbox-group>
</div>`,
    }),
};

export const MinMax: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-checkbox-group v-model="checkedCities" :min="1" :max="2">
		<fb-checkbox
			v-for="city in cities"
			:key="city"
			:label="city"
			:value="city"
		>
			{{ city }}
		</fb-checkbox>
	</fb-checkbox-group>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const checkedCities = ref(['New York', 'Washington']);
const cities = ['New York', 'Washington', 'Los Angeles', 'Chicago'];
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbCheckbox, FbCheckboxGroup },
        setup: () => {
            const checkedCities = ref(["New York", "Washington"]);
            const cities = ["New York", "Washington", "Los Angeles", "Chicago"];

            return {
                checkedCities,
                cities,
            };
        },
        template: `
<div class="fb-checkbox-story-block">
	<fb-checkbox-group v-model="checkedCities" :min="1" :max="2">
		<fb-checkbox
			v-for="city in cities"
			:key="city"
			:label="city"
			:value="city"
		>
			{{ city }}
		</fb-checkbox>
	</fb-checkbox-group>
</div>`,
    }),
};

export const Button: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<div>
		<fb-checkbox-group v-model="checkboxGroup1" size="${ComponentSizeTypes.LARGE}">
			<fb-checkbox-button v-for="city in cities" :key="city" :value="city">
				{{ city }}
			</fb-checkbox-button>
		</fb-checkbox-group>
	</div>
	<div>
		<fb-checkbox-group v-model="checkboxGroup2">
			<fb-checkbox-button v-for="city in cities" :key="city" :value="city">
				{{ city }}
			</fb-checkbox-button>
		</fb-checkbox-group>
	</div>
	<div>
		<fb-checkbox-group v-model="checkboxGroup3" size="${ComponentSizeTypes.SMALL}">
			<fb-checkbox-button
				v-for="city in cities"
				:key="city"
				:value="city"
				:disabled="city === 'Beijing'"
			>
				{{ city }}
			</fb-checkbox-button>
		</fb-checkbox-group>
	</div>
	<div>
		<fb-checkbox-group v-model="checkboxGroup4" size="${ComponentSizeTypes.SMALL}" disabled>
			<fb-checkbox-button v-for="city in cities" :key="city" :value="city">
				{{ city }}
			</fb-checkbox-button>
		</fb-checkbox-group>
	</div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const checkboxGroup1 = ref(['New York']);
const checkboxGroup2 = ref(['Washington']);
const checkboxGroup3 = ref(['Los Angeles']);
const checkboxGroup4 = ref(['Chicago']);
const cities = ['New York', 'Washington', 'Los Angeles', 'Chicago'];
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbCheckbox, FbCheckboxGroup, FbCheckboxButton },
        setup: () => {
            const checkboxGroup1 = ref(["New York"]);
            const checkboxGroup2 = ref(["Washington"]);
            const checkboxGroup3 = ref(["Los Angeles"]);
            const checkboxGroup4 = ref(["Chicago"]);
            const cities = ["New York", "Washington", "Los Angeles", "Chicago"];

            return {
                checkboxGroup1,
                checkboxGroup2,
                checkboxGroup3,
                checkboxGroup4,
                cities,
            };
        },
        template: `
<div class="fb-checkbox-story-block">
	<div class="fb-checkbox-story-block__item">
		<fb-checkbox-group v-model="checkboxGroup1" size="${ComponentSizeTypes.LARGE}">
			<fb-checkbox-button v-for="city in cities" :key="city" :value="city">
				{{ city }}
			</fb-checkbox-button>
		</fb-checkbox-group>
	</div>
	<div class="fb-checkbox-story-block__item">
		<fb-checkbox-group v-model="checkboxGroup2">
			<fb-checkbox-button v-for="city in cities" :key="city" :value="city">
				{{ city }}
			</fb-checkbox-button>
		</fb-checkbox-group>
	</div>
	<div class="fb-checkbox-story-block__item">
		<fb-checkbox-group v-model="checkboxGroup3" size="${ComponentSizeTypes.SMALL}">
			<fb-checkbox-button
				v-for="city in cities"
				:key="city"
				:value="city"
				:disabled="city === 'Beijing'"
			>
				{{ city }}
			</fb-checkbox-button>
		</fb-checkbox-group>
	</div>
	<div class="fb-checkbox-story-block__item">
		<fb-checkbox-group v-model="checkboxGroup4" size="${ComponentSizeTypes.SMALL}" disabled>
			<fb-checkbox-button v-for="city in cities" :key="city" :value="city">
				{{ city }}
			</fb-checkbox-button>
		</fb-checkbox-group>
	</div>
</div>`,
    }),
};

export const Bordered: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<div>
		<fb-checkbox v-model="checked1" label="Option1" size="${ComponentSizeTypes.LARGE}" border />
		<fb-checkbox v-model="checked2" label="Option2" size="${ComponentSizeTypes.LARGE}" border />
	</div>
	<div>
		<fb-checkbox v-model="checked3" label="Option1" border />
		<fb-checkbox v-model="checked4" label="Option2" border />
	</div>
	<div>
		<fb-checkbox-group v-model="checkboxGroup1" size="${ComponentSizeTypes.SMALL}">
			<fb-checkbox label="Option1" value="Value1" border />
			<fb-checkbox label="Option2" value="Value2" border />
		</fb-checkbox-group>
	</div>
	<div>
		<fb-checkbox-group v-model="checkboxGroup1" size="${ComponentSizeTypes.SMALL}">
			<fb-checkbox label="Option1" value="Value1" border disabled />
			<fb-checkbox label="Option2" value="Value2" border disabled />
		</fb-checkbox-group>
	</div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const checked1 = ref(true);
const checked2 = ref(false);
const checked3 = ref(false);
const checked4 = ref(true);
const checkboxGroup1 = ref(['Value1']);
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbCheckbox, FbCheckboxGroup },
        setup: () => {
            const checked1 = ref(true);
            const checked2 = ref(false);
            const checked3 = ref(false);
            const checked4 = ref(true);
            const checkboxGroup1 = ref(["Value1"]);

            return {
                checked1,
                checked2,
                checked3,
                checked4,
                checkboxGroup1,
            };
        },
        template: `
<div class="fb-checkbox-story-block">
	<div class="fb-checkbox-story-block__item">
		<fb-checkbox v-model="checked1" label="Option1" size="${ComponentSizeTypes.LARGE}" border />
		<fb-checkbox v-model="checked2" label="Option2" size="${ComponentSizeTypes.LARGE}" border />
	</div>
	<div class="fb-checkbox-story-block__item">
		<fb-checkbox v-model="checked3" label="Option1" border />
		<fb-checkbox v-model="checked4" label="Option2" border />
	</div>
	<div class="fb-checkbox-story-block__item">
		<fb-checkbox-group v-model="checkboxGroup1" size="${ComponentSizeTypes.SMALL}">
			<fb-checkbox label="Option1" value="Value1" border />
			<fb-checkbox label="Option2" value="Value2" border />
		</fb-checkbox-group>
	</div>
	<div class="fb-checkbox-story-block__item">
		<fb-checkbox-group v-model="checkboxGroup1" size="${ComponentSizeTypes.SMALL}">
			<fb-checkbox label="Option1" value="Value1" border disabled />
			<fb-checkbox label="Option2" value="Value2" border disabled />
		</fb-checkbox-group>
	</div>
</div>`,
    }),
};
