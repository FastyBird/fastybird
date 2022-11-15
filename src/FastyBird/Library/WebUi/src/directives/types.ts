export interface ITouchHorizontalDirectiveBinding {
	value: (pan: ITouchHorizontalChanges) => void;
	oldValue?: (pan: ITouchHorizontalChanges) => void;
	modifiers: ITouchHorizontalDirectiveBindingModifiers;
}

export interface ITouchHorizontalDirectiveBindingModifiers {
	vertical?: boolean;
	horizontal?: boolean;
	mouse?: boolean;
	mousePrevent?: boolean;
	mouseMightPrevent?: boolean;
	mouseStop?: boolean;
	mouseAllDir?: boolean;
	prevent?: boolean;
	mightPrevent?: boolean;
	stop?: boolean;
}

export type TTouchDirection = 'left' | 'right' | 'up' | 'down';

export interface ITouchHorizontalDirectiveContext {
	handler: (pan: ITouchHorizontalChanges) => void;
	direction: { all: boolean; horizontal: boolean; vertical: boolean };
	event?: {
		x: number;
		y: number;
		time: number;
		mouse: boolean;
		detected: boolean;
		abort: boolean;
		isFirst: boolean;
		isFinal: boolean;
		lastX: number;
		lastY: number;
	};
	mouseStart: (evt: MouseEvent) => void;
	mouseEnd: (evt: MouseEvent) => void;
	start: (evt: MouseEvent | TouchEvent, mouseEvent?: boolean) => void;
	end: (evt: MouseEvent | TouchEvent) => void;
	move: (evt: MouseEvent | TouchEvent) => void;
	touchTargetObserver?: MutationObserver;
}

export interface ITouchHorizontalChanges {
	evt: MouseEvent | TouchEvent;
	position: {
		top: number;
		left: number;
	};
	direction: TTouchDirection;
	isFirst: boolean;
	isFinal: boolean;
	isMouse: boolean;
	duration: number;
	distance: {
		x: number;
		y: number;
	};
	offset: {
		x: number;
		y: number;
	};
	delta: {
		x: number;
		y: number;
	};
}
