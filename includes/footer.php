  </div><!-- /.page-body -->
</main>

<script src="assets/js/app.js"></script>
<script>
  // Live clock
  function tick() {
    const el = document.getElementById('liveClock');
    if (el) el.textContent = new Date().toLocaleTimeString('en-GB');
  }
  tick(); setInterval(tick, 1000);
</script>
</body>
</html>
