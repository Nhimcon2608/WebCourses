    </div> <!-- Đóng div.main-content -->

    <script>
        // JavaScript for dark mode toggle, notifications, etc.
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.querySelector('.dark-mode-toggle');
            
            darkModeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                
                // Lưu trạng thái dark mode vào localStorage
                const isDarkMode = document.body.classList.contains('dark-mode');
                localStorage.setItem('darkMode', isDarkMode ? 'enabled' : 'disabled');
            });
            
            // Kiểm tra trạng thái dark mode lưu trong localStorage
            if (localStorage.getItem('darkMode') === 'enabled') {
                document.body.classList.add('dark-mode');
            }
            
            // Xử lý hiệu ứng hover cho các menu bên sidebar
            const sidebarItems = document.querySelectorAll('.sidebar li a');
            sidebarItems.forEach(item => {
                if (!item.classList.contains('active')) {
                    item.addEventListener('mouseenter', function() {
                        this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                    });
                    item.addEventListener('mouseleave', function() {
                        this.style.backgroundColor = '';
                    });
                }
            });
        });
        
        <?php if (isset($page_script)): ?>
        // Page specific script
        <?php echo $page_script; ?>
        <?php endif; ?>
    </script>
</body>
</html> 