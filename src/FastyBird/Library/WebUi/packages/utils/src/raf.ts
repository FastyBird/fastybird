import { isClient } from './browser';

export const rAF = (fn: () => void): number => (isClient ? window.requestAnimationFrame(fn) : (setTimeout(fn, 16) as any as number));

export const cAF = (handle: number): void => (isClient ? window.cancelAnimationFrame(handle) : clearTimeout(handle));
