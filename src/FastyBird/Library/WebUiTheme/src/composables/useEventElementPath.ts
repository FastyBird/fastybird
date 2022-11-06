import get from "lodash/get";

export function useEventElementPath(): { getPath: ((event: Event) => (EventTarget | Element)[]) } {
  const getPath = (event: Event): (EventTarget | Element)[] => {
    if (get(event, "path") !== null) {
      return get(event, "path", []);
    } else if (get(event, "composedPath") !== null) {
      // @ts-ignore
      return typeof event.composedPath === "function" ? event.composedPath() : get(event, "composedPath", []);
    } else if (get(event, "target") !== null) {
      const path = [];

      let current: HTMLElement | EventTarget | null = event.target;

      while (current) {
        path.push(current);

        if (get(current, "tagName", null) === "HTML") {
          path.push(document);
          path.push(window);

          return path;
        }

        current = get(current, "parentElement", null);
      }
    }

    return [];
  }

  return {
    getPath,
  }
}
