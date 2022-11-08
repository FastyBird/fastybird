import {createLocalVue, mount, Wrapper} from "@vue/test-utils";

import FbButton from "./index.vue";

const localVue = createLocalVue();

describe("Button.vue", () => {
    let wrapper!: Wrapper<Vue>;

    beforeEach(() => {
        wrapper = mount(FbButton, {
            localVue,
        });
    });

    it("fires event when clicked", () => {
        wrapper.find(".my-button").trigger("click");

        const actual = wrapper.emitted().click;

        expect(actual).toBeTruthy();
    });

    it("dont fires click event when disabled", () => {
        wrapper.setProps({
            disabled: true,
        });
        wrapper.find(".my-button").trigger("click");

        const actual = wrapper.emitted().click;

        expect(actual).toBeUndefined();
    });
});
