import { FbFormOrientationTypes, FbSizeTypes } from '@/types';

export interface IFbFormSelectItem {
	name: string;
	value: string | number;
}

export interface IFbFormSelectItemGroup {
	name: string;
	items: IFbFormSelectItem[];
}

export interface IFbFormSelectProps {
	orientation: FbFormOrientationTypes;
	size: FbSizeTypes;
	name: string;
	items: Array<IFbFormSelectItem | IFbFormSelectItemGroup>;
	modelValue?: string | number;
	id: string | null;
	label: string | null;
	required: boolean;
	tabIndex: number | undefined;
	error: string | null;
	blankSelect: string | null;
	readonly: boolean;
	disabled: boolean;
}
