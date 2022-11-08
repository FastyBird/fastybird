import {
    FbFormOrientationTypes,
    FbSizeTypes,
} from '@/types'

export interface IFbFormCheckboxesItem {
    name: string;
    value: string | number | boolean;
}

export interface IFbFormCheckboxesProps {
    orientation: FbFormOrientationTypes;
    size: FbSizeTypes;
    name: string;
    options: IFbFormCheckboxesItem[];
    modelValue?: Array<string | number | boolean>;
    id: string | null;
    label: string | null;
    required: boolean;
    tabIndex: number | undefined;
    error: string | null;
    disabled: boolean;
    readonly: boolean;
}
