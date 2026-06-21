    </main>
    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= sanitize(appConfig()['site_name']) ?>. Stage 3 Admin Panel.</p>
        </div>
    </footer>
    <script>
    // Asynchronously run lead nurturing follow-up checks in background
    setTimeout(function() {
        fetch('<?= url("Backend/api/cron-nurture.php") ?>').catch(function() {});
    }, 2000);
    </script>
</body>
</html>
