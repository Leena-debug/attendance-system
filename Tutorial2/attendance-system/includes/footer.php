    <footer style="background: #ad1457; color: white; text-align: center; padding: 20px; margin-top: 50px;">
        <p>&copy; <?php echo date('Y'); ?> Attendance Pro - Smart Attendance Management System</p>
        <p style="opacity: 0.8; font-size: 14px; margin-top: 5px;">Built with ❤️ using PHP & MySQL</p>
    </footer>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Add smooth scrolling to all links
            $("a").on('click', function(event) {
                if (this.hash !== "") {
                    event.preventDefault();
                    var hash = this.hash;
                    $('html, body').animate({
                        scrollTop: $(hash).offset().top
                    }, 800, function(){
                        window.location.hash = hash;
                    });
                }
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Add loading state to buttons
            $('form').on('submit', function() {
                $(this).find('button[type="submit"]').html('⏳ Processing...').prop('disabled', true);
            });
        });
    </script>
</body>
</html>