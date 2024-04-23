import { isClient } from '../browser';

const globalNodes: HTMLElement[] = [];
let target: HTMLElement = !isClient ? (undefined as any) : document.body;

export function createGlobalNode(id?: string): HTMLDivElement {
	const el = document.createElement('div');

	if (id !== undefined) {
		el.setAttribute('id', id);
	}

	target.appendChild(el);
	globalNodes.push(el);

	return el;
}

export function removeGlobalNode(el: HTMLElement): void {
	globalNodes.splice(globalNodes.indexOf(el), 1);
	el.remove();
}

export function changeGlobalNodesTarget(el: HTMLElement): void {
	if (el === target) {
		return;
	}

	target = el;
	globalNodes.forEach((el) => {
		if (el.contains(target) === false) {
			target.appendChild(el);
		}
	});
}
