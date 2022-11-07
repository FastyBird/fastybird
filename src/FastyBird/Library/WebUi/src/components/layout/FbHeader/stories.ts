import {
  Args,
  Meta,
  Story,
} from "@storybook/vue3";
import { ref } from "vue";

import {
  FbLayoutHeader,
  FbLayoutHeaderButton,
  FbLayoutHeaderContent,
  FbLayoutHeaderHeading,
} from "./index";
import { FbMenuItemTypes } from "@/types";

export default {
  component: FbLayoutHeader,
  title: "Components/Layout/FB Header",
  argTypes: {
    default: {
      type: { name: "string", required: true },
      control: { type: "text" },
      description: "Content body slot",
      table: {
        type: { summary: "string" },
        defaultValue: { summary: "-" },
      },
    },
    header: {
      type: { name: "string", required: false },
      control: { type: "text" },
      description: "Content header slot",
      table: {
        type: { summary: "string" },
        defaultValue: { summary: "-" },
      },
    },
    footer: {
      type: { name: "string", required: false },
      control: { type: "text" },
      description: "Content footer slot",
      table: {
        type: { summary: "string" },
        defaultValue: { summary: "-" },
      },
    },
  },
  parameters: {
    controls: { disabled: true },
  },
} as Meta;

interface ITemplateArgs extends Args {
  default?: string
  header?: string
  footer?: string
}

const Template: Story<ITemplateArgs> = (args) => {
  return {
    components: {
      FbLayoutHeader,
      FbLayoutHeaderHeading,
      FbLayoutHeaderButton,
      FbLayoutHeaderContent,
    },
    setup(): any {
      const menuCollapsed = ref<boolean>(true);

      return { args, menuCollapsed };
    },
    template: `
      <div style="height: 150px">
        <fb-layout-header
          style="display: block !important;"
          :menuCollapsed="menuCollapsed"
          @toggleMenu="menuCollapsed = !menuCollapsed"
        >
          <template #heading>
            <fb-layout-header-heading
              :heading="'Simple header content'"
              :sub-heading="'With short sub-header'"
              :teleport="false"
            />
          </template>

          <template #button-small>
            <fb-layout-header-button
              :teleport="false"
              small
              type="${FbMenuItemTypes.BUTTON}"
            >
              Close
            </fb-layout-header-button>
          </template>

          <template #sub-content>
            <fb-layout-header-content :teleport="false">Bottom custom content</fb-layout-header-content>
          </template>
        </fb-layout-header>
      </div>
    `,
  };
};

export const Default = Template.bind({});

Default.args = {
};
