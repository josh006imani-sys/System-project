<?php
// footer.php — This closes the main content and the page
// I just put this at the bottom of every page so the HTML closes properly
// My lecturer said every page needs a footer so here it is
?>

</main> <!-- closing the main content div -->

<script>
// I added this small script for the mobile menu
// It toggles the sidebar when you click the menu button on small screens
document.getElementById('menuToggle')?.addEventListener('click', function() {
    var sidebar = document.getElementById('sidebar');
    if (sidebar.style.display === 'block') {
        sidebar.style.display = 'none';
    } else {
        sidebar.style.display = 'block';
    }
});

// This is for the user dropdown in the top bar
// I saw this on a tutorial and copied it
document.getElementById('userMenuTrigger')?.addEventListener('click', function(e) {
    e.stopPropagation();
    var menu = document.getElementById('userMenu');
    if (menu.style.display === 'block') {
        menu.style.display = 'none';
    } else {
        menu.style.display = 'block';
    }
});

// Click anywhere else to close the dropdown
// I got this from stackoverflow
document.addEventListener('click', function() {
    var menu = document.getElementById('userMenu');
    if (menu) menu.style.display = 'none';
});
</script>

</body>
</html>