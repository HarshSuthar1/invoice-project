export const qs = (selector, parent = document) =>
  parent.querySelector(selector);

export const qsa = (selector, parent = document) =>
  [...parent.querySelectorAll(selector)];

export const on = (event, selector, handler) => {
  document.addEventListener(event, e => {
    if (e.target.matches(selector)) {
      handler(e);
    }
  });
};
