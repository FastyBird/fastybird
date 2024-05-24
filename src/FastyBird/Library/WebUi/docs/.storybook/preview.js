import "@fastybird/web-ui-theme-chalk/src/index.scss";

import "./storybook-styles.css";

export const parameters = {
    viewMode: "docs",
    controls: {
        matchers: {
            color: /(background|color)$/i,
            date: /Date$/,
        },
    },
    backgrounds: {
        disable: true,
    },
    darkMode: {
        darkClass: "dark",
        lightClass: "light",
        stylePreview: true,
        classTarget: "html",
    },
};
