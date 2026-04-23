document.addEventListener('DOMContentLoaded', () => {
    // Check if we are on a small screen
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (window.innerWidth <= 768) {
        if(menuToggle) menuToggle.style.display = 'block';
    }

    window.addEventListener('resize', () => {
        if (window.innerWidth <= 768) {
            if(menuToggle) menuToggle.style.display = 'block';
        } else {
            if(menuToggle) menuToggle.style.display = 'none';
            if(sidebar) sidebar.classList.remove('active');
        }
    });

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
});
