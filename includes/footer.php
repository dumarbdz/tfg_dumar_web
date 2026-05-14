</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <p class="footer-copy">Dumar Bermudez</p>
    </div>
</footer>
<script src="/js/app.js" defer></script>
<?php $flash = flash_get(); if ($flash): ?>
<script>showToast(<?= json_encode((string)$flash['msg']) ?>, <?= json_encode((string)$flash['type']) ?>);</script>
<?php endif; ?>
</body>
</html>
