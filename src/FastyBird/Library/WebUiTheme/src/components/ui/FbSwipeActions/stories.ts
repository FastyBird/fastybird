import {
  Args,
  Meta,
  Story,
} from '@storybook/vue3'

import { IFbUiSwipeActionsListProps } from './types'
import { FbUiSwipeActionsList } from './index'

export default {
  component: FbUiSwipeActionsList,
  title: 'Components/UI/FB Swipe actions',
  argTypes: {
    default: {
      type: { name: 'string', required: false },
      control: { type: 'text' },
      description: 'Footer custom content slot',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: '-' },
      },
    },
  },
  parameters: {
    controls: { disabled: true },
  },
} as Meta

interface TemplateArgs extends IFbUiSwipeActionsListProps<any>, Args {
  default?: string
}

const Template: Story<TemplateArgs> = (args) => {
  return {
    components: { FbUiSwipeActionsList },
    setup(): any {
      return { args }
    },
    template: `
      <fb-ui-swipe-actions-list
        :items="args.items"
        itemKey="id"
      >
        <template #default="{ item, index, revealLeft, revealRight, close }">
          <div style="width: 100%; background-color: white; border-radius: 3px; box-shadow: none; border: 1px solid lightgray; padding: 1rem;">
            <h2>{{ item.title }}</h2>
            <p>{{ item.description }}</p>
            <span>{{ index }}</span>
          </div>
        </template>

        <template #left="{ item, close }">
          <div style="display: flex; align-items: center; padding: 0 3rem; cursor: pointer; left: 0; color: white; background-color: rgb(255, 59, 48);">
            <font-awesome-icon icon="trash" />
          </div>

          <div style="display: flex; align-items: center; padding: 0 3rem; cursor: pointer; left: 0; color: white; background-color: rgb(88, 86, 214);">
            <font-awesome-icon icon="close" />
          </div>
        </template>

        <template #right="{ item }">
          <div style="display: flex; align-items: center; padding: 0 3rem; cursor: pointer; left: 0; color: white; background-color: rgb(0, 122, 255);">
            <font-awesome-icon icon="heart" />
          </div>

          <div style="display: flex; align-items: center; padding: 0 3rem; cursor: pointer; left: 0; color: white; background-color: rgb(76, 217, 100);">
            <font-awesome-icon icon="heart" />
          </div>
        </template>

        <template #empty>
          <div>
            list is empty ( filtered or just empty )
          </div>
        </template>
      </fb-ui-swipe-actions-list>
    `,
  }
}

export const Default = Template.bind({})

Default.args = {
  items: [
    {
      id: 'a',
      title: 'Some title',
      description: 'some description',
      disabled: false,
    },
    {
      id: 'b',
      title: 'Some title',
      description: 'some description',
      disabled: false,
    },
    {
      id: 'c',
      title: 'Some title',
      description: 'some description',
      disabled: false,
    },
  ],
}
