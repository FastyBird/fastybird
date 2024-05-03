export const composeEventHandlers = <E>(
	theirsHandler?: (event: E) => boolean | void,
	oursHandler?: (event: E) => void,
	{ checkForDefaultPrevented = true } = {}
): ((event: E) => void) => {
	return (event: E): void => {
		const shouldPrevent = theirsHandler?.(event);

		if (!checkForDefaultPrevented || !shouldPrevent) {
			oursHandler?.(event);
		}
	};
};

type WhenMouseHandler = (e: PointerEvent) => any;

export const whenMouse = (handler: WhenMouseHandler): WhenMouseHandler => {
	return (e: PointerEvent) => (e.pointerType === 'mouse' ? handler(e) : undefined);
};
