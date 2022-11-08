import './spacing.scss';
import { Story } from '@storybook/vue3';

export default {
	title: 'About/Spacing',
};

export const Spacing: Story = () => {
	return {
		template: `
      <div class="fb-story-spacing__container">
        <p>Extra small (5px):</p>
        <div class="fb-story-spacing__box-extra-small" />
        <p>Small (10px):</p>
          <div class="fb-story-spacing__box-small" />
        <p>Medium (15px):</p>
        <div class="fb-story-spacing__box-medium" />
        <p>Large (30px):</p>
        <div class="fb-story-spacing__box-large" />
      </div>
    `,
	};
};
