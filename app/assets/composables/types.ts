import { ComputedRef } from 'vue';

export interface UseBreakpoints {
	isXSDevice: ComputedRef<boolean>;
	isSMDevice: ComputedRef<boolean>;
	isMDDevice: ComputedRef<boolean>;
	isLGDevice: ComputedRef<boolean>;
	isXLDevice: ComputedRef<boolean>;
	isXXLDevice: ComputedRef<boolean>;
}
