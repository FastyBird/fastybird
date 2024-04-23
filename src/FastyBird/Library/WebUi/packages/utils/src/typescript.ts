export const mutable = <T extends readonly any[] | Record<string, unknown>>(val: T): Mutable<typeof val> => val as Mutable<typeof val>;
export type Mutable<T> = { -readonly [P in keyof T]: T[P] };

export type HTMLElementCustomized<T> = HTMLElement & T;

export type Arrayable<T> = T | T[];
export type Awaitable<T> = Promise<T> | T;
