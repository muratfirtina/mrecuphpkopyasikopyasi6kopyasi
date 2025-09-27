/**
 * Mr ECU - Notification System JavaScript
 * Bildirim Sistemi JavaScript Fonksiyonları
 */

// Bildirim işaretleme fonksiyonları
function markNotificationRead(notificationId) {
    // AJAX ile bildirimi okundu olarak işaretle
    fetch('../ajax/mark_notification_read.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Bildirim sayısını güncelle
            updateNotificationBadge();
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

function markAllNotificationsRead() {
    // AJAX ile tüm bildirimleri okundu olarak işaretle
    fetch('../ajax/mark_all_notifications_read.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Sayfa yenile veya bildirim alanını güncelle
            location.reload();
        } else {
            console.error('Error marking all notifications as read:', data.message);
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
    });
}

function updateNotificationBadge() {
    // Okunmamış bildirim sayısını güncelle
    fetch('../ajax/get_notification_count.php', {
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('notification-badge');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
    })
    .catch(error => {
        console.error('Error updating notification badge:', error);
    });
}

// Sayfa yüklendiğinde bildirim sayısını kontrol et
document.addEventListener('DOMContentLoaded', function() {
    // Her 30 saniyede bir bildirim sayısını güncelle
    setInterval(updateNotificationBadge, 30000);
    
    // Bildirim dropdown'larını kapat
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            const dropdowns = document.querySelectorAll('.dropdown-menu.show');
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
});

// Email test fonksiyonu (admin paneli için)
function sendTestEmail(toEmail) {
    if (!toEmail) {
        toEmail = prompt('Test email göndermek için email adresi girin:');
        if (!toEmail) return;
    }
    
    fetch('../ajax/send-test-email.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            to_email: toEmail
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Test email başarıyla gönderildi!');
        } else {
            alert('Test email gönderilemedi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error sending test email:', error);
        alert('Test email gönderilirken hata oluştu.');
    });
}

// Toast bildirim gösterme
function showToastNotification(title, message, type = 'info') {
    // Bootstrap toast kullanarak bildirim göster
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type === 'file_upload' ? 'success' : type === 'revision_request' ? 'warning' : 'info'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    // Toast container'ı oluştur (yoksa)
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Toast'ı ekle
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Toast'ı göster
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
    });
    toast.show();
    
    // Toast gösterildikten sonra DOM'dan kaldır
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// Bildirim sesi çalma (isteğe bağlı)
function playNotificationSound() {
    try {
        // Basit beep sesi
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    } catch (e) {
        console.log('Could not play notification sound:', e);
    }
}

// Real-time bildirim kontrolü (basit polling)
function startNotificationPolling() {
    setInterval(function() {
        fetch('../ajax/get_notification_count.php', {
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count > 0) {
                updateNotificationBadge();
                // Yeni bildirim varsa ses çal (isteğe bağlı)
                // playNotificationSound();
            }
        })
        .catch(error => {
            console.error('Error checking notifications:', error);
        });
    }, 60000); // Her dakika kontrol et
}

// Sayfa yüklendiğinde polling'i başlat
document.addEventListener('DOMContentLoaded', function() {
    startNotificationPolling();
});
