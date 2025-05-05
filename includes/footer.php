        </main>
    </div> <!-- /.main-content -->
    
    <footer class="footer mt-auto py-3 bg-light <?= isset($_SESSION['user_id']) ? 'main-content' : '' ?>">
        <div class="container-fluid text-center">
            <p class="text-muted mb-0">&copy; <?= date('Y') ?> <?= APP_NAME ?> - Todos os direitos reservados</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (required for some Bootstrap functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Flatpickr for Date/Time -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    
    <!-- Chart.js for Dashboard -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom scripts -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/forms.js"></script>
    
    <script>
    // Timeout para inatividade (5 minutos)
    var inactivityTime = function() {
        var time;
        window.onload = resetTimer;
        document.onmousemove = resetTimer;
        document.onkeypress = resetTimer;
        document.onscroll = resetTimer;
        document.onclick = resetTimer;
        
        function logout() {
            window.location.href = 'logout.php?reason=inactivity';
        }
        
        function resetTimer() {
            clearTimeout(time);
            time = setTimeout(logout, 5 * 60 * 1000); // 5 minutos em milisegundos
        }
    };
    
    // Iniciar monitoramento de inatividade
    inactivityTime();
    
    // Toggle sidebar no mobile
    document.getElementById('sidebar-toggle')?.addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
    });
    </script>
</body>
</html>
