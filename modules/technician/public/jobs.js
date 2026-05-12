document.addEventListener('DOMContentLoaded', () => {
  const grid = document.getElementById('jobsGrid');
  if (!grid) return;

  // Module-native filtering: do NOT fetch or re-render.
  // We filter the server-rendered cards using `data-status`.
  const filterBtns = Array.from(document.querySelectorAll('[data-filter]'));
  const cards = () => Array.from(grid.querySelectorAll('[data-status]'));
  let currentFilter = 'all';

  function setActive(btn) {
    filterBtns.forEach((b) => b.classList.toggle('is-active', b === btn));
  }

  function applyFilter() {
    const list = cards();
    const want = currentFilter;

    list.forEach((card) => {
      const status = card.getAttribute('data-status') || '';
      const ok = (want === 'all') ? true : status === want;
      card.style.display = ok ? '' : 'none';
    });
  }

  filterBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      setActive(btn);
      currentFilter = btn.getAttribute('data-filter') || 'all';
      applyFilter();
    });
  });

  // Initial state
  const initial = filterBtns.find((b) => b.classList.contains('is-active')) || filterBtns[0];
  if (initial) {
    currentFilter = initial.getAttribute('data-filter') || 'all';
    setActive(initial);
  }
  applyFilter();
});