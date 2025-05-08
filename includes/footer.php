</main>
    </div> <!-- /.main-content -->
    
    <footer class="footer mt-auto py-3 bg-light <?= isset($_SESSION['user_id']) ? 'main-content' : '' ?>">
        <div class="container-fluid text-center">
            <p class="text-muted mb-0">&copy; <?= date('Y') ?> <?= APP_NAME ?> - Todos os direitos reservados - Desenvolvido por Willyma de Jesus</p>
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
    
    <!-- Sortable.js for drag-and-drop functionality -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <!-- Font Awesome para ícones - CORRIGIDO para resolver erro CORS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Custom styles -->
    <link rel="stylesheet" href="assets/css/upload.css">
    <link rel="stylesheet" href="assets/css/cards.css">
    <link rel="stylesheet" href="assets/css/agendamento.css">
    <link rel="stylesheet" href="assets/css/calendar-fix.css">
    
    <!-- Custom scripts - Carregando apenas scripts essenciais -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/forms.js"></script>
    
    <!-- IMPORTANTE: Carregar apenas o script de upload simplificado -->
    <script src="assets/js/simple-upload.js"></script>
    
    <!-- Scripts para funcionalidades específicas da página -->
    <script src="assets/js/agendamento.js"></script>
    <script src="assets/js/calendar-init.js"></script>
    
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

    // Remover qualquer texto estranho no final da página
    document.addEventListener('DOMContentLoaded', function() {
        // Função para remover nós de texto que contêm números estranhos
        function removeStrangeTextNodes() {
            const bodyNode = document.body;
            const walker = document.createTreeWalker(
                bodyNode, 
                NodeFilter.SHOW_TEXT, 
                { 
                    acceptNode: function(node) {
                        // Verificar se o nó de texto contém números em formato estranho
                        if (node.nodeValue && /\d+:\d+:\d+:\d+/.test(node.nodeValue)) {
                            return NodeFilter.FILTER_ACCEPT;
                        }
                        // Verificar se o nó contém uma sequência longa de números
                        if (node.nodeValue && /\d{10,}/.test(node.nodeValue)) {
                            return NodeFilter.FILTER_ACCEPT;
                        }
                        return NodeFilter.FILTER_SKIP;
                    }
                },
                false
            );

            const nodesToRemove = [];
            while (walker.nextNode()) {
                nodesToRemove.push(walker.currentNode);
            }

            // Remover os nós identificados
            nodesToRemove.forEach(function(node) {
                if (node.parentNode) {
                    node.parentNode.removeChild(node);
                }
            });
        }

        // Executar a limpeza após o carregamento da página
        removeStrangeTextNodes();
        
        // Executar novamente após um curto intervalo para pegar elementos adicionados dinamicamente
        setTimeout(removeStrangeTextNodes, 500);
        
        // Adicionar um limpador de previews para garantir que não haja duplicações
        const previews = document.querySelectorAll('#singlePreview, #carouselPreview');
        if (previews && previews.length > 0) {
            previews.forEach(preview => {
                if (preview) preview.innerHTML = '';
            });
        }
    });
    </script>
</body>
</html>