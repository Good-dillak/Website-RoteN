// ========================================
// PORTAL BERITA WISATA - SCRIPT.JS
// Fitur: Search, Back to Top, Loading, Toast, Subscription AJAX, Voting AJAX
// ========================================

// Utility: Debounce (Dipindahkan ke luar DOMContentLoaded)
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

document.addEventListener('DOMContentLoaded', function () {

    // 1. Back to Top Button
    const backToTop = document.createElement('div');
    backToTop.className = 'back-to-top'; // Pastikan CSS untuk .back-to-top ada
    backToTop.innerHTML = '↑';
    backToTop.title = 'Kembali ke atas';
    document.body.appendChild(backToTop);

    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('show');
        } else {
            backToTop.classList.remove('show');
        }
    });

    backToTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // 2. Live Search (jika ada form search)
    const searchForm = document.querySelector('form[role="search"]');
    if (searchForm) {
        const input = searchForm.querySelector('input[name="q"]');
        const results = document.getElementById('search-results');

        input?.addEventListener('input', debounce(function () {
            const q = this.value.trim();
            if (q.length < 2) {
                if (results) results.innerHTML = '';
                return;
            }

            fetch(`search.php?q=${encodeURIComponent(q)}`)
                .then(r => r.text())
                .then(html => {
                    if (results) results.innerHTML = html;
                });
        }, 300));
    }

    // 3. Toast Notification
    window.showToast = function (message, type = 'success') {
        // Mapping tipe ke kelas Bootstrap yang benar
        let bgClass = 'bg-success';
        if (type === 'error' || type === 'danger') {
            bgClass = 'bg-danger';
        } else if (type === 'warning') {
            bgClass = 'bg-warning text-dark';
        } else if (type === 'info') {
            bgClass = 'bg-info';
        }
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white ${bgClass} border-0 position-fixed`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        document.body.appendChild(toast);
        new bootstrap.Toast(toast, { delay: 4000 }).show();
        setTimeout(() => toast.remove(), 4500);
    };

    // 4. Auto-slug generator (admin)
    const titleInput = document.querySelector('input[name="title"]');
    const slugInput = document.querySelector('input[name="slug"]');
    if (titleInput && slugInput) {
        titleInput.addEventListener('blur', function () {
            if (!slugInput.value) {
                slugInput.value = this.value.toLowerCase()
                    .replace(/[^a-z0-9 -]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim();
            }
        });
    }

    // 5. Image Preview
    const fileInput = document.querySelector('input[type="file"]');
    const preview = document.getElementById('image-preview');
    if (fileInput && preview) {
        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => preview.src = e.target.result;
                reader.readAsDataURL(file);
                preview.style.display = 'block';
            }
        });
    }

    // 6. Confirm Delete
    document.querySelectorAll('a[data-confirm]').forEach(link => {
        link.addEventListener('click', e => {
            if (!confirm(link.dataset.confirm || 'Hapus item ini?')) {
                e.preventDefault();
            }
        });
    });

    // 7. Loading Overlay (Diperbaiki menggunakan Bootstrap Spinner)
    window.showLoading = function () {
        let overlay = document.getElementById('loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(255,255,255,0.9); z-index: 9999;
                display: flex; align-items: center; justify-content: center;
                flex-direction: column; gap: 1rem; font-size: 1.2rem;
            `;
            // Menggunakan class text-primary (sesuai skema warna Magenta Anda)
            overlay.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Memuat...</span>
                </div>
                <div>Memproses...</div>
            `;
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    };

    window.hideLoading = function () {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) overlay.style.display = 'none';
    };

    // 8. Form Submit with Loading (Default Admin Form)
    // Mengecualikan form yang memiliki data-ajax (seperti Langganan)
    document.querySelectorAll('form:not([data-ajax])').forEach(form => {
        form.addEventListener('submit', function () {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                // Menggunakan Bootstrap spinner class
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';
                showLoading();
            }
        });
    });

    // ----------------------------------------------------
    // 9. Fungsionalitas AJAX Berlangganan (Subscription)
    // Menargetkan form dengan id="subscribe-form" yang kini memiliki data-ajax="subscribe"
    // ----------------------------------------------------
    const subscribeForm = document.getElementById('subscribe-form'); // Menggunakan ID yang sudah ada
    if (subscribeForm) {
        subscribeForm.setAttribute('data-ajax', 'subscribe'); // Tambahkan atribut ini secara programatis atau di HTML
        subscribeForm.addEventListener('submit', function(e) {
            e.preventDefault(); 
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';

            fetch(form.action, {
                method: 'POST',
                // Membuat body dari data form
                body: new URLSearchParams(new FormData(form)),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(response => response.json())
            .then(data => {
                // Tampilkan pesan menggunakan Toast Notification
                window.showToast(data.message, data.status);
                if (data.status === 'success') {
                    form.reset(); 
                }
            })
            .catch(error => {
                console.error('Error subscription:', error);
                window.showToast('Gagal terhubung ke server.', 'danger');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }

    // ----------------------------------------------------
    // 10. Fungsionalitas AJAX Voting (Like/Dislike)
    // ----------------------------------------------------
    document.querySelectorAll('.vote-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const btn = this;
            const articleId = btn.dataset.id;
            const action = btn.dataset.action;
            
            if (btn.disabled) return;
            btn.disabled = true;

            fetch('vote.php', { // Kirim ke file vote.php yang baru
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${articleId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                window.showToast(data.message, data.status);
                
                if (data.status === 'success') {
                    // Update tampilan counter suara
                    document.getElementById(`likes-count-${articleId}`).textContent = data.likes;
                    document.getElementById(`dislikes-count-${articleId}`).textContent = data.dislikes;
                    
                    // (Opsional) Tambahkan logika untuk menandai tombol aktif/tidak aktif di sini
                }
            })
            .catch(error => {
                console.error('Error voting:', error);
                window.showToast('Gagal terhubung ke server voting.', 'danger');
            })
            .finally(() => {
                btn.disabled = false;
            });
        });
    });
});