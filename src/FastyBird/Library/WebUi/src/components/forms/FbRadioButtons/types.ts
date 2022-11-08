import {
    FbFormOrientationTypes,
    FbSizeTypes,
} from '@/types';

export interface IFbFormRadioButtonsItem {
    name: string;
    value: string | number | boolean;
}

export interface IFbFormRadioButtonsProps {
    orientation: FbFormOrientationTypes;
    size: FbSizeTypes;
    name: string;
    options: IFbFormRadioButtonsItem[];
    modelValue?: string | number | boolean;
    id: string | null;
    label: string | null;
    required: boolean;
    tabIndex: number | undefined;
    error: string | null;
    readonly: boolean;
    disabled: boolean;
}
