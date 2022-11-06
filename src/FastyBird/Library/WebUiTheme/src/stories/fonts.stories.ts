import "!style-loader!css-loader!sass-loader!./fonts.scss";
import { Story } from "@storybook/vue";

export default {
  title: "About/Fonts",
};

export const Fonts: Story = () => {
  return {
    template: `
      <div class="fb-story-fonts__container">
        <h1>H1 Headline No. 1</h1>
        <h2>H2 Headline No. 2</h2>
        <h3>H3 Headline No. 3</h3>
        <h4>H4 Headline No. 4</h4>
        <h5>H5 Headline No. 5</h5>
        <h6>H6 Headline No. 6</h6>

        <hr>

        <p>
          This is normal paragraph text! Lorem ipsum dolor sit amet, consectetur adipiscing
          elit, sed do eiusmod tempor
          incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation
          ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate
          velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
          culpa qui officia deserunt mollit anim id est laborum.
        </p>

        <p>
          <a href="#">This is an inline link</a>
        </p>
      </div>
    `,
  };
};
