import {
    FbFormOrientationTypes,
    FbSizeTypes,
} from '@/types'

export interface IFbFormLabelProps {
    id: string;
    orientation: FbFormOrientationTypes;
    size: FbSizeTypes;
    required: boolean;
    hasError: boolean;
}
