import '@testing-library/jest-dom';

// Vitest 3.2 + jsdom 26 : le flag --localstorage-file passé sans chemin valide
// crée un localStorage incomplet (sans .clear). On fournit un mock fiable.
const createLocalStorageMock = () => {
  let store = new Map();
  return {
    getItem:    (key)        => store.get(key) ?? null,
    setItem:    (key, value) => store.set(key, String(value)),
    removeItem: (key)        => store.delete(key),
    clear:      ()           => store.clear(),
    get length()             { return store.size; },
    key:        (index)      => ([...store.keys()][index] ?? null),
  };
};

Object.defineProperty(window, 'localStorage', {
  value:        createLocalStorageMock(),
  writable:     true,
  configurable: true,
});
