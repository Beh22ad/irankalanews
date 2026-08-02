// همبرگر منو
const hamburger = document.getElementById('hamburgerBtn');
const nav = document.getElementById('mainNav');
if (hamburger && nav) {
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('open');
        nav.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
        if (!hamburger.contains(e.target) && !nav.contains(e.target)) {
            hamburger.classList.remove('open');
            nav.classList.remove('open');
        }
    });
}

// کپی API Key — با fallback برای HTTP
function copyText(text, btn) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showCopied(btn);
        }).catch(() => {
            fallbackCopy(text, btn);
        });
    } else {
        fallbackCopy(text, btn);
    }
}

function fallbackCopy(text, btn) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    ta.style.top = '-9999px';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    try {
        document.execCommand('copy');
        showCopied(btn);
    } catch(e) {
        btn.textContent = 'خطا';
    }
    document.body.removeChild(ta);
}

function showCopied(btn) {
    var orig = btn.textContent;
    btn.textContent = 'کپی شد!';
    btn.style.background = '#15803d';
    setTimeout(() => {
        btn.textContent = orig;
        btn.style.background = '';
    }, 1500);
}

// کپی API Key
document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        var key = btn.getAttribute('data-key');
        if (key) copyText(key, btn);
    });
});

// آپلود فیش - پیش‌نمایش و drag & drop
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('receiptFile');
const previewImg = document.getElementById('previewImg');

if (uploadArea && fileInput) {
    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            showPreview(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            showPreview(fileInput.files[0]);
        }
    });

    function showPreview(file) {
        if (!file.type.startsWith('image/')) return;
        var reader = new FileReader();
        reader.onload = (e) => {
            if (previewImg) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
}

// رفرش کپچا با کلیک روی تصویر
document.querySelectorAll('.captcha-image').forEach(img => {
    img.addEventListener('click', () => {
        img.src = '/captcha.php?t=' + Date.now();
    });
});

// تأیید حذف
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
        if (!confirm(el.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    });
});

// ========================================
// مدیریت تب‌های نمونه کد در مستندات
// ========================================
const tabBtns = document.querySelectorAll('.code-tab-btn');
const tabContents = document.querySelectorAll('.code-tab-content');

tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        // حذف کلاس active از همه دکمه‌ها و محتواها
        tabBtns.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));

        // اضافه کردن کلاس active به دکمه کلیک شده و محتوای مرتبط
        btn.classList.add('active');
        const targetId = 'tab-' + btn.getAttribute('data-tab');
        const targetContent = document.getElementById(targetId);
        if (targetContent) {
            targetContent.classList.add('active');
        }
    });
});