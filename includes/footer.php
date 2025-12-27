        </div> <!-- Close container from header -->
    </main>
    
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Dolphin CRM. INFO2180 Project 2.</p>
            <p class="mt-1">Group Project - Customer Relationship Management System</p>
        </div>
    </footer>
    
    <!-- JavaScript for AJAX functionality -->
    <script>
    // AJAX filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Filter button clicks
        const filterButtons = document.querySelectorAll('.filter-btn');
        filterButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!this.classList.contains('active')) {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get filter value from data attribute
                    const filter = this.getAttribute('data-filter');
                    if (filter) {
                        // Update URL with filter parameter
                        window.location.href = 'dashboard.php?filter=' + filter;
                    }
                }
            });
        });
        
        // Update filter buttons based on current URL
        const urlParams = new URLSearchParams(window.location.search);
        const currentFilter = urlParams.get('filter') || 'all';
        const activeButton = document.querySelector(`.filter-btn[data-filter="${currentFilter}"]`);
        if (activeButton) {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            activeButton.classList.add('active');
        }
    });
    </script>
</body>
</html>