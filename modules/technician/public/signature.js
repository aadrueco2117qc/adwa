/* Signature capture (canvas) — fixed: lazy init so canvas sizes correctly even when tab is hidden */
(function () {

  function initCanvas(canvas) {
    const dpr = Math.max(1, window.devicePixelRatio || 1);
    const rect = canvas.getBoundingClientRect();
    const w = rect.width  || canvas.offsetWidth  || canvas.parentElement.offsetWidth || 400;
    const h = rect.height || canvas.offsetHeight || 200;
    canvas.width  = Math.floor(w * dpr);
    canvas.height = Math.floor(h * dpr);
    const ctx = canvas.getContext('2d');
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.scale(dpr, dpr);
    ctx.lineWidth   = 2;
    ctx.lineCap     = 'round';
    ctx.lineJoin    = 'round';
    ctx.strokeStyle = '#111827';
    return ctx;
  }

  function setup(canvas) {
    if (!canvas) return null;

    let ctx        = null;   // initialised lazily on first pointer event
    let drawing    = false;
    let last       = null;
    let hasStrokes = false;
    let enabled    = false;  // disabled until setEnabled(true) is called

    // Hide the placeholder text once the user starts drawing
    const placeholder = document.getElementById('sigPlaceholder');

    function ensureInit() {
      if (ctx) return;
      ctx = initCanvas(canvas);
    }

    function posFromEvent(e) {
      const r = canvas.getBoundingClientRect();
      const p = e.touches ? e.touches[0] : e;
      return {
        x: (p.clientX - r.left),
        y: (p.clientY - r.top)
      };
    }

    function start(e) {
      if (!enabled) { e.preventDefault(); return; }
      ensureInit();
      drawing = true;
      last    = posFromEvent(e);
      if (placeholder) placeholder.style.display = 'none';
      e.preventDefault();
    }

    function move(e) {
      if (!drawing || !enabled) return;
      ensureInit();
      const p = posFromEvent(e);
      ctx.beginPath();
      ctx.moveTo(last.x, last.y);
      ctx.lineTo(p.x,    p.y);
      ctx.stroke();
      last = p;
      hasStrokes = true;
      e.preventDefault();
    }

    function end(e) {
      drawing = false;
      last    = null;
    }

    canvas.addEventListener('mousedown',  start);
    canvas.addEventListener('mousemove',  move);
    window.addEventListener('mouseup',    end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove',  move,  { passive: false });
    canvas.addEventListener('touchend',   end);

    // Re-init if canvas is resized (e.g. window resize)
    const ro = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(() => {
      if (!ctx) return;  // not yet initialised, no need to reset
      const saved = canvas.toDataURL();
      ctx = initCanvas(canvas);
      const img = new Image();
      img.onload = () => ctx.drawImage(img, 0, 0, canvas.getBoundingClientRect().width, canvas.getBoundingClientRect().height);
      img.src = saved;
    }) : null;
    if (ro) ro.observe(canvas);

    function applyCanvasStyle() {
      canvas.style.cursor = enabled ? 'crosshair' : 'not-allowed';
      canvas.style.opacity = enabled ? '1' : '0.5';
    }

    return {
      setEnabled(val) {
        enabled = !!val;
        applyCanvasStyle();
        // If disabling mid-draw, stop the stroke
        if (!enabled) { drawing = false; last = null; }
      },
      clear() {
        ensureInit();
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasStrokes = false;
        if (placeholder) placeholder.style.display = '';
      },
      toDataUrl() {
        ensureInit();
        return canvas.toDataURL('image/png');
      },
      isBlank() {
        if (!hasStrokes) return true;
        ensureInit();
        const data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
        for (let i = 3; i < data.length; i += 4) {
          if (data[i] !== 0) return false;
        }
        return true;
      }
    };
  }

  window.MRTS = window.MRTS || {};
  window.MRTS.signature = { setup };
})();