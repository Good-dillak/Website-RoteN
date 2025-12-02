<?php // admin/includes/footer.php ?>
</div><!-- /.container-fluid -->

<footer class="bg-light border-top py-5 mt-5">
    <div class="container text-center text-muted">
        <p>&copy; <?= date('Y') ?> NusaRoteMalole. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/script.js"></script>

<!-- Flash Message Toast -->
<?php if (isset($_SESSION['flash']) && $_SESSION['flash']): 
    $f = $_SESSION['flash']; unset($_SESSION['flash']);
?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof showToast === 'function') {
            showToast(<?= json_encode(htmlspecialchars($f['message'])) ?>, <?= json_encode($f['type']) ?>);
        }
    });
</script>
<?php endif; ?>

</body>
</html>