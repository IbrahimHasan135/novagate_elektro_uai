</main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateTime() {
            const now = new Date();
            const options = { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('current-time').textContent = now.toLocaleDateString('id-ID', options);
        }
        updateTime();
        setInterval(updateTime, 1000);
        
        function autoRefresh() {
            const path = window.location.pathname;
            if (path.endsWith('/dashboard.php') || path === '/') {
                setTimeout(() => location.reload(), 30000);
            }
        }
        autoRefresh();
    </script>
</body>
</html>