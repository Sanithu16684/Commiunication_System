<?php
// includes/footer.php - Footer layout and global utilities
?>
    </div> <!-- End .main-wrapper -->
</div> <!-- End .app-wrapper -->

<!-- Global Toast Container -->
<div class="toast-container" id="toast-container"></div>

<script>
// Live clock
function updateClock() {
    const el = document.getElementById('live-clock');
    if (el) {
        const now = new Date();
        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        el.querySelector('span').textContent = timeStr;
    }
}
setInterval(updateClock, 1000);
updateClock();

// Global Toast System
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = 'bi-check-circle-fill';
    if (type === 'error') icon = 'bi-x-circle-fill';
    if (type === 'warning') icon = 'bi-exclamation-triangle-fill';
    
    toast.innerHTML = `<i class="bi ${icon}" style="font-size: 18px; color: inherit;"></i> <span>${message}</span>`;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// Modal helper functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Close modal when clicking on backdrop
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.classList.remove('show');
    }
});
</script>
</body>
</html>
