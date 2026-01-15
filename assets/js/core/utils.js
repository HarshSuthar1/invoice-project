export const formatCurrency = (amount) =>
  `₹${Number(amount || 0).toFixed(2)}`;

export const debounce = (fn, delay = 300) => {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
};
