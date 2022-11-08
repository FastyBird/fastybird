import {Plugin} from "vue";

export interface IPluginOptions {
    directives?: boolean;
    mixins?: boolean;
}

export type InstallFunction = Plugin & { installed?: boolean };

export * from "../components/types";
export * from "../directives/types";

export enum FbMenuItemTypes {
    LINK = "link",
    VUE_LINK = "vue_link",
    BUTTON = "button",
    TEXT = "text",
    BLANK = "blank",
}

export enum FbSizeTypes {
    EXTRA_SMALL = "xs",
    SMALL = "sm",
    MEDIUM = "md",
    LARGE = "lg",
    EXTRA_LARGE = "xl",
    NONE = "none",
}

export enum FbFormInputTypeTypes {
    TEXT = "text",
    PASSWORD = "password",
    EMAIL = "email",
    NUMBER = "number",
    TEL = "tel",
    URL = "url",
}

export enum FbFormOrientationTypes {
    VERTICAL = "vertical",
    HORIZONTAL = "horizontal",
    INLINE = "inline",
}

export enum FbFormResultTypes {
    NONE = "none",
    WORKING = "working",
    ERROR = "error",
    OK = "ok",
}

export enum FbUiVariantTypes {
    DEFAULT = "default",
    PRIMARY = "primary",
    SUCCESS = "success",
    DANGER = "danger",
    WARNING = "warning",
    INFO = "info",
}

export enum FbUiModalLayoutTypes {
    DEFAULT = "default",
    PHONE = "phone",
    TABLET = "tablet",
}

export enum FbUiButtonActionsTypes {
    LINK = "link",
    VUE_LINK = "vue_link",
    BUTTON = "button",
}

export enum FbUiButtonButtonTypes {
    BUTTON = "button",
    SUBMIT = "submit",
    RESET = "reset",
}

export enum FbUiButtonVariantTypes {
    DEFAULT = "default",
    PRIMARY = "primary",
    SUCCESS = "success",
    DANGER = "danger",
    WARNING = "warning",
    INFO = "info",
    OUTLINE_DEFAULT = "outline-default",
    OUTLINE_PRIMARY = "outline-primary",
    OUTLINE_SUCCESS = "outline-success",
    OUTLINE_DANGER = "outline-danger",
    OUTLINE_WARNING = "outline-warning",
    OUTLINE_INFO = "outline-info",
    LINK = "link",
    LINK_DEFAULT = "link-default",
}

export enum FbUiConfirmationWindowPrimaryButtonTypes {
    YES = "yes",
    NO = "no",
}

export enum FbUiDividerTypeTypes {
    HORIZONTAL = "horizontal",
    VERTICAL = "vertical",
}

export enum FbUiDividerVariantTypes {
    DEFAULT = "default",
    GRADIENT = "gradient",
}

export enum FbUiTextAlignTypes {
    NONE = "none",
    LEFT = "left",
    RIGHT = "right",
    CENTER = "center",
    JUSTIFY = "justify",
}

export enum FbUiItemVariantTypes {
    DEFAULT = 'default',
    LIST = 'list',
}

export enum FbLayoutHeaderHeadingAlign {
    LEFT = "left",
    RIGHT = "right",
    CENTER = "center",
}
