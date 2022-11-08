import {
    FbFormOrientationTypes,
    FbSizeTypes,
} from '@/types';

export interface IFbFormFieldProps {
    orientation: FbFormOrientationTypes;
    size: FbSizeTypes;
    name: string;
    id: string | null;
    label: string | null;
    required: boolean;
    isFocused: boolean;
    hasValue: boolean;
    error: string | null;
}
