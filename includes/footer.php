</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <p class="footer-brand">Mundial Store — Camisetas del Mundial 2026</p>
        <nav class="footer-nav" aria-label="Enlaces del pie de página">
            <a href="/catalog.php">Catálogo</a>
            <a href="/cart.php">Carrito</a>
            <a href="/contact.php">Contacto</a>
            <?php if (current_user() === null): ?>
                <a href="/login.php">Iniciar sesión</a>
            <?php else: ?>
                <a href="/orders.php">Mis pedidos</a>
            <?php endif; ?>
        </nav>
        <p class="footer-copy">&copy; <?= date('Y') ?> Mundial Store. Proyecto TFG DAW.</p>
    </div>
</footer>
<script src="/js/app.js" defer></script>
<?php $flash = flash_get(); if ($flash): ?>
<script>showToast(<?= json_encode((string)$flash['msg']) ?>, <?= json_encode((string)$flash['type']) ?>);</script>
<?php endif; ?>
</body>
</html>
