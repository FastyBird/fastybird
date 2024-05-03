import { NOOP } from '@vue/shared';

export const useSameTarget = (
	handleClick?: (e: MouseEvent) => void
): { onClick: (e: MouseEvent) => void; onMousedown: (e: MouseEvent) => void; onMouseup: (e: MouseEvent) => void } => {
	if (!handleClick) {
		return { onClick: NOOP, onMousedown: NOOP, onMouseup: NOOP };
	}

	let mousedownTarget = false;
	let mouseupTarget = false;

	// refer to this https://javascript.info/mouse-events-basics
	// events fired in the order: mousedown -> mouseup -> click
	// we need to set the mousedown handle to false after click fired.
	const onClick = (e: MouseEvent): void => {
		// if and only if
		if (mousedownTarget && mouseupTarget) {
			handleClick(e);
		}
		mousedownTarget = mouseupTarget = false;
	};

	const onMousedown = (e: MouseEvent): void => {
		// marking current mousedown target.
		mousedownTarget = e.target === e.currentTarget;
	};

	const onMouseup = (e: MouseEvent): void => {
		// marking current mouseup target.
		mouseupTarget = e.target === e.currentTarget;
	};

	return { onClick, onMousedown, onMouseup };
};
