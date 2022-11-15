import { Args, Meta, Story } from '@storybook/vue3';
import { action } from '@storybook/addon-actions';
import { ref } from 'vue';

import { FbSizeTypes } from '../../../types';
import FbFormRadioButtonsGroup from '../FbRadioButtonsGroup/index.vue';

import { IFbFormRadioButtonProps } from './types';
import FbFormRadioButton from './index.vue';

export default {
	component: FbFormRadioButton,
	title: 'Components/Form/FB Radio button',
	argTypes: {
		default: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			defaultValue: undefined,
			description: 'Radio button custom label slot',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		name: {
			type: { name: 'string', required: true },
			control: { type: 'text' },
			defaultValue: 'field-name',
		},
		option: {
			type: { name: 'string', required: true },
			control: { type: 'text' },
			defaultValue: null,
		},
		value: {
			type: { name: 'string', required: true },
			control: { type: 'text' },
			defaultValue: null,
		},
		size: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			defaultValue: FbSizeTypes.MEDIUM,
			options: [FbSizeTypes.SMALL, FbSizeTypes.MEDIUM, FbSizeTypes.LARGE],
			description: 'Field size',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: FbSizeTypes.MEDIUM },
			},
		},
		id: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			defaultValue: null,
		},
		label: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			defaultValue: 'Radio buttons field label',
		},
		tabIndex: {
			type: { name: 'number', required: false },
			control: { type: 'text' },
			defaultValue: undefined,
		},
		hasError: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			defaultValue: false,
		},
		readonly: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			defaultValue: false,
		},
		disabled: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			defaultValue: false,
		},
	},
	parameters: {
		controls: { disabled: true },
	},
} as Meta;

interface TemplateArgs extends IFbFormRadioButtonProps, Args {
	default?: string;
}

const Template: Story<TemplateArgs> = (args) => {
	return {
		components: { FbFormRadioButton },
		setup(): any {
			return { args };
		},
		template: `
      <fb-form-radio-button
        v-model="args.value"
        :size="args.size"
        :name="args.name"
        :id="args.id"
        :option="args.option"
        :label="args.label"
        :tab-index="args.tabIndex"
        :has-error="args.hasError"
        :readonly="args.readonly"
        :disabled="args.disabled"
        @change="onChange"
      >
        <template v-if="${args.default !== null && typeof args.default !== 'undefined'}" #default>${args.default}</template>
      </fb-form-radio-button>
    `,
		methods: {
			onChange: action('field-changed'),
		},
	};
};

export const Default = Template.bind({});

export const WithCustomLabel = Template.bind({});

WithCustomLabel.args = {
	default: 'Some custom <strong>html</strong> label <font-awesome-icon icon="info-circle" />',
};

export const InGroup: Story<TemplateArgs> = (args) => {
	return {
		components: { FbFormRadioButton, FbFormRadioButtonsGroup },
		setup(): any {
			const radioGroup = ref<HTMLElement | null>(null);
			const value = ref(null);

			return { args, radioGroup, value };
		},
		template: `
      <fb-form-radio-buttons-group
        v-model="value"
        ref="radioGroup"
      >
        <div style="display: flex; flex-direction: row; margin-bottom: 10px;">
          <div style="width: 100px; font-size: 13px;">
            Select this
          </div>
          <div>
            <fb-form-radio-button
              :group="radioGroup"
              name="option_one"
              option="option_one"
              @change="onChange"
            />
          </div>
        </div>
        <div style="display: flex; flex-direction: row;">
          <div style="width: 100px; font-size: 13px;">
            Or this
          </div>
          <div>
            <fb-form-radio-button
              :group="radioGroup"
              name="option_two"
              option="option_two"
              @change="onChange"
            />
          </div>
        </div>
      </fb-form-radio-buttons-group>
    `,
		methods: {
			onChange: action('field-changed'),
		},
	};
};
