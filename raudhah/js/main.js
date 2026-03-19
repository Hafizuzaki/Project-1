// =====================================================
// main.js - JavaScript Utama PT Raudhah Amanah Wisata
// Semua JS tergabung dalam satu file
// =====================================================

'use strict';

// ---- DOM Ready ----
document.addEventListener('DOMContentLoaded', function () {
    initNavbar();
    initSidebar();
    initModals();
    initCopyReferral();
    initAlertDismiss();
    initAOS();
    initFileUploadPreview();
    initConfirmDialogs();
    initNumberFormat();
    initTreeToggle();
    initNotifDropdown();
});

// =====================================================
// NAVBAR
// =====================================================
function initNavbar() {
    const hamburger = document.querySelector('.hamburger');
    const menu      = document.querySelector('.navbar-menu');
    if (!hamburger || !menu) return;

    hamburger.addEventListener('click', function () {
        menu.classList.toggle('open');
        hamburger.classList.toggle('active');
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
        if (!hamburger.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('open');
            hamburger.classList.remove('active');
        }
    });

    // Scroll effect
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function () {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }
}

// =====================================================
// SIDEBAR (Dashboard)
// =====================================================
function initSidebar() {
    const toggleBtn = document.querySelector('#sidebar-toggle');
    const sidebar   = document.querySelector('.sidebar');
    const overlay   = document.querySelector('#sidebar-overlay');

    if (!toggleBtn || !sidebar) return;

    toggleBtn.addEventListener('click', function () {
        sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('active');
    });

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // Active link highlight
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-link').forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').split('/').pop().replace('.php', ''))) {
            link.classList.add('active');
        }
    });
}

// =====================================================
// MODALS
// =====================================================
function initModals() {
    // Open modal
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-modal');
            openModal(targetId);
        });
    });

    // Close modal
    document.querySelectorAll('.modal-close, [data-modal-close]').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = this.closest('.modal-overlay');
            if (modal) closeModal(modal.id);
        });
    });

    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) closeModal(this.id);
        });
    });

    // Close on ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(m => {
                closeModal(m.id);
            });
        }
    });
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// =====================================================
// COPY REFERRAL CODE
// =====================================================
function initCopyReferral() {
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const code    = this.closest('.referral-code-display')?.querySelector('.referral-code-text')?.textContent?.trim();
            const copyTarget = this.getAttribute('data-copy');
            const textToCopy = code || copyTarget || '';

            if (!textToCopy) return;

            if (navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    showCopied(this);
                });
            } else {
                // Fallback
                const textarea = document.createElement('textarea');
                textarea.value = textToCopy;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showCopied(this);
            }
        });
    });
}

function showCopied(btn) {
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Tersalin!';
    btn.classList.add('copied');
    setTimeout(() => {
        btn.innerHTML = original;
        btn.classList.remove('copied');
    }, 2000);
}

// =====================================================
// ALERT DISMISS
// =====================================================
function initAlertDismiss() {
    document.querySelectorAll('.alert[data-dismissible]').forEach(alert => {
        const btn = document.createElement('button');
        btn.innerHTML = '&times;';
        btn.style.cssText = 'float:right;background:none;border:none;font-size:1.2rem;cursor:pointer;margin-left:1rem;opacity:0.7;line-height:1;';
        btn.addEventListener('click', () => alert.remove());
        alert.insertBefore(btn, alert.firstChild);

        // Auto dismiss
        const timeout = parseInt(alert.getAttribute('data-timeout') || '0');
        if (timeout > 0) {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, timeout);
        }
    });
}

// =====================================================
// SIMPLE AOS (Animate on Scroll)
// =====================================================
function initAOS() {
    const elements = document.querySelectorAll('[data-aos]');
    if (!elements.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el    = entry.target;
                const delay = el.getAttribute('data-aos-delay') || 0;
                setTimeout(() => el.classList.add('aos-animate'), parseInt(delay));
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.15 });

    elements.forEach(el => observer.observe(el));
}

// =====================================================
// FILE UPLOAD PREVIEW
// =====================================================
function initFileUploadPreview() {
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
        input.addEventListener('change', function () {
            const previewId = this.getAttribute('data-preview');
            const preview   = document.getElementById(previewId);
            if (!preview) return;

            const file = this.files[0];
            if (!file) return;

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }

            // Show file name
            const nameDisplay = document.querySelector(`[data-filename="${previewId}"]`);
            if (nameDisplay) nameDisplay.textContent = file.name;
        });
    });

    // Drag and drop upload areas
    document.querySelectorAll('.upload-area').forEach(area => {
        area.addEventListener('dragover', e => {
            e.preventDefault();
            area.classList.add('drag-over');
        });
        area.addEventListener('dragleave', () => area.classList.remove('drag-over'));
        area.addEventListener('drop', e => {
            e.preventDefault();
            area.classList.remove('drag-over');
            const input = area.querySelector('input[type="file"]');
            if (input && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
        area.addEventListener('click', () => {
            const input = area.querySelector('input[type="file"]');
            if (input) input.click();
        });
    });
}

// =====================================================
// CONFIRM DIALOGS
// =====================================================
function initConfirmDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    });
}

// =====================================================
// NUMBER FORMAT (Rupiah input auto-format)
// =====================================================
function initNumberFormat() {
    document.querySelectorAll('input[data-rupiah]').forEach(input => {
        input.addEventListener('input', function () {
            let value  = this.value.replace(/[^\d]/g, '');
            this.value = value ? parseInt(value).toLocaleString('id-ID') : '';
        });
        input.addEventListener('blur', function () {
            const hiddenId = this.getAttribute('data-rupiah');
            const hidden   = document.getElementById(hiddenId);
            if (hidden) {
                hidden.value = this.value.replace(/[^\d]/g, '');
            }
        });
    });
}

// =====================================================
// TREE TOGGLE
// =====================================================
function initTreeToggle() {
    document.querySelectorAll('.tree-node-card[data-toggle-children]').forEach(card => {
        card.addEventListener('click', function () {
            const childrenId = this.getAttribute('data-toggle-children');
            const children   = document.getElementById(childrenId);
            if (children) {
                children.style.display = children.style.display === 'none' ? '' : 'none';
                this.classList.toggle('collapsed');
            }
        });
    });
}

// =====================================================
// NOTIFICATION DROPDOWN
// =====================================================
function initNotifDropdown() {
    const btn      = document.querySelector('#notif-btn');
    const dropdown = document.querySelector('#notif-dropdown');
    if (!btn || !dropdown) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('active');
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== btn) {
            dropdown.classList.remove('active');
        }
    });

    // Mark read on open
    btn.addEventListener('click', function () {
        if (dropdown.classList.contains('active')) {
            markNotificationsRead();
        }
    });
}

function markNotificationsRead() {
    fetch('<?php echo APP_URL; ?>/php/user_actions.php?action=mark_notif_read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_csrf_token=' + (document.querySelector('[name="_csrf_token"]')?.value || '')
    }).then(() => {
        const dot = document.querySelector('.notif-dot');
        if (dot) dot.remove();
        const badge = document.querySelector('#notif-count');
        if (badge) badge.remove();
    }).catch(() => {});
}

// =====================================================
// UTILITIES / HELPERS
// =====================================================
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.cssText = `
        position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999;
        background:${type === 'success' ? '#27AE60' : type === 'danger' ? '#C0392B' : '#2980B9'};
        color:#fff; padding:1rem 1.5rem; border-radius:10px;
        box-shadow:0 8px 24px rgba(0,0,0,0.15); font-size:0.95rem;
        animation:fadeInUp 0.4s ease; max-width:350px; word-wrap:break-word;
    `;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'}"></i> ${message}`;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.4s';
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}

function formatRupiah(number) {
    return 'Rp ' + parseInt(number).toLocaleString('id-ID');
}

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// =====================================================
// FORM VALIDATION
// =====================================================
function validateForm(formId) {
    const form   = document.getElementById(formId);
    if (!form) return true;

    let valid = true;
    form.querySelectorAll('[required]').forEach(field => {
        const val = field.value.trim();
        field.classList.remove('is-invalid');

        if (!val) {
            field.classList.add('is-invalid');
            valid = false;
        }
        if (field.type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            field.classList.add('is-invalid');
            valid = false;
        }
    });

    // Password match
    const pass1 = form.querySelector('[name="password"]');
    const pass2 = form.querySelector('[name="confirm_password"]');
    if (pass1 && pass2 && pass1.value !== pass2.value) {
        pass2.classList.add('is-invalid');
        valid = false;
        showToast('Password tidak cocok!', 'danger');
    }

    return valid;
}

// =====================================================
// AJAX DATA TABLE SEARCH
// =====================================================
function initDataTableSearch(tableId, searchId) {
    const search = document.getElementById(searchId);
    const rows   = document.querySelectorAll(`#${tableId} tbody tr`);

    if (!search) return;

    search.addEventListener('input', debounce(function () {
        const q = this.value.toLowerCase();
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    }, 200));
}

// =====================================================
// REFERRAL TREE RENDERER (Pure JS)
// =====================================================
class ReferralTreeRenderer {
    constructor(containerId, treeData) {
        this.container = document.getElementById(containerId);
        this.data      = treeData;
        if (this.container && this.data) this.render();
    }

    render() {
        this.container.innerHTML = this.renderNode(this.data, true);
        this.bindEvents();
    }

    renderNode(node, isRoot = false) {
        if (!node) return '';

        const statusClass = `status-${node.status}`;
        const rootClass   = isRoot ? 'is-root' : '';
        const initial     = node.full_name ? node.full_name.charAt(0).toUpperCase() : '?';
        const hasChildren = node.children && (node.children.left || node.children.right);

        let childrenHtml = '';
        if (hasChildren) {
            const leftHtml  = node.children.left  ? this.renderNode(node.children.left)  : this.renderEmptySlot('Kiri');
            const rightHtml = node.children.right ? this.renderNode(node.children.right) : this.renderEmptySlot('Kanan');
            childrenHtml = `
                <div class="tree-line-down"></div>
                <div class="tree-children" id="children-${node.id}">
                    <div class="tree-node-wrapper">${leftHtml}</div>
                    <div class="tree-node-wrapper">${rightHtml}</div>
                </div>
            `;
        } else if (!isRoot) {
            const leftSlot  = this.renderEmptySlot('Kiri');
            const rightSlot = this.renderEmptySlot('Kanan');
            childrenHtml = `
                <div class="tree-line-down"></div>
                <div class="tree-children">
                    <div class="tree-node-wrapper">${leftSlot}</div>
                    <div class="tree-node-wrapper">${rightSlot}</div>
                </div>
            `;
        }

        return `
            <div class="tree-node" data-id="${node.id}">
                <div class="tree-node-card ${rootClass} ${statusClass}" data-node-id="${node.id}" title="${node.full_name}">
                    <div class="node-avatar">${initial}</div>
                    <div class="node-name">${this.truncate(node.full_name, 14)}</div>
                    <div class="node-code">${node.referral_code}</div>
                    ${!isRoot ? `<div class="node-pos">${node.position === 'left' ? '⬅ Kiri' : '➡ Kanan'}</div>` : ''}
                </div>
                ${childrenHtml}
            </div>
        `;
    }

    renderEmptySlot(label) {
        return `
            <div class="tree-node">
                <div class="tree-node-card empty-slot">
                    <div class="node-avatar" style="background:var(--gray-300);color:var(--gray-600);">+</div>
                    <div class="node-name" style="color:var(--gray-400)">Slot ${label}</div>
                    <div class="node-code" style="color:var(--gray-400)">Kosong</div>
                </div>
            </div>
        `;
    }

    bindEvents() {
        this.container.querySelectorAll('.tree-node-card:not(.empty-slot)').forEach(card => {
            card.addEventListener('click', function () {
                const nodeId  = this.getAttribute('data-node-id');
                const details = document.getElementById('node-detail');
                if (details) {
                    document.querySelectorAll('.tree-node-card').forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    // Trigger custom event
                    document.dispatchEvent(new CustomEvent('nodeSelected', { detail: { id: nodeId } }));
                }
            });
        });
    }

    truncate(str, max) {
        return str && str.length > max ? str.slice(0, max) + '…' : str;
    }
}

// =====================================================
// SMOOTH SCROLL
// =====================================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href === '#') return;
        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// =====================================================
// AUTO-HIDE ALERTS AFTER 5s
// =====================================================
setTimeout(() => {
    document.querySelectorAll('.alert-auto-hide').forEach(alert => {
        alert.style.transition = 'opacity 0.5s, max-height 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// =====================================================
// SIDEBAR TOGGLE (mobile)
// =====================================================
(function initSidebarMobile() {
    const sidebar     = document.getElementById('sidebar');
    const overlay     = document.getElementById('sidebarOverlay');
    const openBtn     = document.getElementById('sidebarOpen');
    const closeBtn    = document.getElementById('sidebarClose');

    if (!sidebar) return;

    function openSidebar() {
        sidebar.classList.add('open');
        overlay && overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay && overlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    openBtn  && openBtn.addEventListener('click', openSidebar);
    closeBtn && closeBtn.addEventListener('click', closeSidebar);
    overlay  && overlay.addEventListener('click', closeSidebar);
})();

// =====================================================
// NOTIFICATION DROPDOWN
// =====================================================
(function initNotifToggle() {
    const btn      = document.getElementById('notifBtn');
    const dropdown = document.getElementById('notifDropdown');
    if (!btn || !dropdown) return;

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });
    document.addEventListener('click', function() {
        dropdown.classList.remove('open');
    });
    dropdown.addEventListener('click', function(e) { e.stopPropagation(); });
})();

// =====================================================
// FILE UPLOAD PREVIEW
// =====================================================
(function initUploadPreview() {
    const fileInput = document.getElementById('proofImage');
    const preview   = document.getElementById('imagePreview');
    const placeholder = document.querySelector('.file-upload-placeholder');
    if (!fileInput || !preview) return;

    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder && (placeholder.style.display = 'none');
        };
        reader.readAsDataURL(file);
    });

    // Drag & drop
    const area = document.getElementById('fileUploadArea');
    if (area) {
        area.addEventListener('dragover', e => { e.preventDefault(); area.style.borderColor = 'var(--gold)'; });
        area.addEventListener('dragleave', () => { area.style.borderColor = ''; });
        area.addEventListener('drop', e => {
            e.preventDefault();
            area.style.borderColor = '';
            const files = e.dataTransfer.files;
            if (files.length) fileInput.files = files;
            fileInput.dispatchEvent(new Event('change'));
        });
    }
})();

// =====================================================
// COPY BUTTONS (bank accounts)
// =====================================================
document.querySelectorAll('.copy-btn[data-clipboard]').forEach(btn => {
    btn.addEventListener('click', function() {
        const text = this.dataset.clipboard;
        navigator.clipboard.writeText(text).then(() => {
            const orig = this.textContent;
            this.textContent = '✓ Tersalin!';
            this.style.color = '#27ae60';
            setTimeout(() => { this.textContent = orig; this.style.color = ''; }, 2000);
        });
    });
});

// =====================================================
// CONFIRM DIALOGS
// =====================================================
document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const msg = this.dataset.confirm || 'Apakah Anda yakin?';
        if (!confirm(msg)) e.preventDefault();
    });
});
