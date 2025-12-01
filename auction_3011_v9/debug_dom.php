<?php
// Temporary debug page to inspect DOM & JS state for the auction_system project
// Safe to remove after debugging.
require_once __DIR__ . '/header.php';
?>

<div class="container py-4">
  <h2>Debug: DOM & JS 状态</h2>
  <p>此页面帮助在浏览器中直接查看关键元素和脚本加载情况。页面会捕获 JS 错误并显示检测项结果。</p>

  <h4>Server info</h4>
  <ul>
    <li>Server time: <?php echo date('Y-m-d H:i:s'); ?></li>
    <li>PHP version: <?php echo phpversion(); ?></li>
    <li>Document root: <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'n/a'); ?></li>
  </ul>

  <h4>JS error log</h4>
  <div id="jsErrors" class="alert alert-warning" style="white-space:pre-wrap; max-height:200px; overflow:auto;">(no errors yet)</div>

  <h4>Detection results</h4>
  <table class="table table-sm table-bordered" id="resultsTable">
    <thead><tr><th>Check</th><th>Result</th></tr></thead>
    <tbody></tbody>
  </table>

  <button id="runChecks" class="btn btn-primary">Run checks now</button>
  <button id="clearErrors" class="btn btn-secondary">Clear captured errors</button>

  <h4>Script tags on page</h4>
  <ul id="scriptList"></ul>

  <h4>Quick element inspector</h4>
  <p>输入 CSS 选择器（例如 <code>#auctionImages</code> 或 <code>#editImagesInput</code>），回车查看该元素的属性与存在性。</p>
  <input id="quickSelector" class="form-control" placeholder="#selector">
  <pre id="quickInspect" style="background:#f8f9fa;padding:12px;">(no selection)</pre>

  <hr>
  <p class="text-muted">调试完成后可以直接删除此文件。</p>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

<script>
(function(){
  const errors = [];
  const jsErrorsEl = document.getElementById('jsErrors');
  function pushError(e){
    const msg = (e && e.message) ? e.message : String(e);
    errors.push(msg);
    jsErrorsEl.textContent = errors.join('\n\n');
  }
  window.addEventListener('error', function(ev){
    pushError(ev.error || ev.message || 'Unknown error');
  });
  window.addEventListener('unhandledrejection', function(ev){
    pushError('UnhandledRejection: ' + (ev.reason && ev.reason.message ? ev.reason.message : JSON.stringify(ev.reason)));
  });

  function addRow(name, value){
    const tbody = document.querySelector('#resultsTable tbody');
    const tr = document.createElement('tr');
    const td1 = document.createElement('td'); td1.textContent = name;
    const td2 = document.createElement('td'); td2.textContent = value;
    tr.appendChild(td1); tr.appendChild(td2);
    tbody.appendChild(tr);
  }

  function runChecks(){
    // clear previous
    document.querySelector('#resultsTable tbody').innerHTML = '';
    document.getElementById('scriptList').innerHTML = '';

    addRow('DOMContentLoaded state', document.readyState);
    addRow('window.flatpickr loaded', !!window.flatpickr);
    addRow('DataTransfer supported', typeof DataTransfer !== 'undefined');
    addRow('navigator.userAgent', navigator.userAgent);

    // check common selectors used by create/manage pages
    const selectors = ['#auction_end', '#auction_end_date', '#auctionImages', '#imagesPreview', '#editImagesInput', '#editImagesPreview', '.flatpickr-input'];
    selectors.forEach(s => {
      const el = document.querySelector(s);
      if(!el){ addRow(s, 'NOT FOUND'); return; }
      const info = [];
      info.push('tag='+el.tagName.toLowerCase());
      if(el.type) info.push('type='+el.type);
      if(el.multiple) info.push('multiple');
      if(el.getAttribute) {
        const step = el.getAttribute('step');
        if(step) info.push('step='+step);
        const min = el.getAttribute('min');
        if(min) info.push('min='+min);
        const accept = el.getAttribute('accept');
        if(accept) info.push('accept='+accept);
      }
      // flatpickr instance check
      if(el._flatpickr) info.push('has flatpickr instance');
      addRow(s, info.join('; '));
    });

    // show script tags
    Array.from(document.scripts).forEach(s => {
      const li = document.createElement('li');
      li.textContent = s.src || '(inline)';
      document.getElementById('scriptList').appendChild(li);
    });

    addRow('captured JS errors count', errors.length);
  }

  document.getElementById('runChecks').addEventListener('click', runChecks);
  document.getElementById('clearErrors').addEventListener('click', function(){ errors.length=0; jsErrorsEl.textContent='(cleared)'; runChecks(); });

  // quick selector inspector
  document.getElementById('quickSelector').addEventListener('keydown', function(ev){
    if(ev.key === 'Enter'){
      ev.preventDefault();
      const sel = this.value.trim();
      const out = document.getElementById('quickInspect');
      if(!sel){ out.textContent='(empty selector)'; return; }
      try{
        const el = document.querySelector(sel);
        if(!el){ out.textContent='NOT FOUND'; return; }
        const props = {
          tag: el.tagName,
          id: el.id,
          classes: el.className,
          outerHTML: el.outerHTML.slice(0,1000)
        };
        out.textContent = JSON.stringify(props, null, 2);
      }catch(err){ out.textContent = 'Error: '+err.message; }
    }
  });

  // run once after a short delay to allow external scripts to initialize
  setTimeout(runChecks, 800);
})();
</script>
