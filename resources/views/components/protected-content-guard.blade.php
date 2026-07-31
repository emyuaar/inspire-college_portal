@props(['learnerName' => '', 'learnerEmail' => '', 'courseName' => '', 'courseId' => null, 'lessonId' => null])

<style>
/* 1. Disable Text Selection */
.protected-content,
.protected-content * {
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
}

/* Disable dragging */
.protected-content img,
.protected-content * {
    -webkit-user-drag: none;
    user-drag: none;
}

/* 5. Disable Print */
@media print {
    body * {
        display: none !important;
        visibility: hidden !important;
    }

    body::before {
        content: "Printing is disabled for protected course content.";
        display: block !important;
        visibility: visible !important;
        font-size: 20px;
        padding: 40px;
        color: #01345B;
    }
}

/* 6. Protected Watermark Overlay */
.protected-watermark-text {
    pointer-events: none;
    position: absolute;
    inset: -100px;
    z-index: 1;
    opacity: 0.045;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-content: center;
    gap: 180px 120px;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: 0.3px;
    color: rgba(1, 52, 91, 0.045);
    transform: rotate(-28deg);
    white-space: nowrap;
    user-select: none;
}

.protected-content {
    position: relative;
    z-index: 2;
}

/* 9. Small Fixed Security Badge */
.protected-security-badge {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 99999;
    background: rgba(1, 52, 91, 0.90);
    color: #ffffff;
    font-size: 12px;
    padding: 8px 12px;
    border-radius: 999px;
    pointer-events: none;
    box-shadow: 0 8px 24px rgba(1, 52, 91, 0.18);
}

/* 7. Blur on Tab Inactive */
body.protected-blur .protected-content {
    filter: blur(14px);
    opacity: 0.25;
    transition: filter 0.3s ease, opacity 0.3s ease;
}

.blur-warning {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(1, 52, 91, 0.92);
    color: white;
    padding: 16px 32px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    z-index: 100000;
    text-align: center;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

body.protected-blur .blur-warning {
    display: block;
}

/* 8. DevTools Detection */
body.devtools-detected .protected-content {
    filter: blur(15px);
    pointer-events: none;
}

.devtools-warning {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #d63384;
    color: white;
    padding: 20px 40px;
    border-radius: 8px;
    font-weight: 600;
    z-index: 100000;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

body.devtools-detected .devtools-warning {
    display: block;
}
</style>

<script>
(function() {
    const logEndpoint = "{{ route('portal.learner.security.store') }}";
    const csrfToken = "{{ csrf_token() }}";
    const courseId = "{{ $courseId ?? '' }}";
    const lessonId = "{{ $lessonId ?? '' }}";

    window.logProtectedEvent = function(eventType) {
        if (!logEndpoint || !csrfToken) return;
        fetch(logEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                event: eventType,
                course_id: courseId,
                lesson_id: lessonId
            })
        }).catch(function() {});
    };

    // 2. Disable Right Click
    document.addEventListener('contextmenu', function (e) {
        if (e.target.closest('.protected-content')) {
            e.preventDefault();
            window.logProtectedEvent('right_click_attempt');
            return false;
        }
    });

    // 3. Disable Copy / Cut / Paste / Select All / Drag
    ['copy', 'cut', 'paste', 'selectstart', 'dragstart'].forEach(function(eventName) {
        document.addEventListener(eventName, function(e) {
            if (e.target.closest('.protected-content')) {
                e.preventDefault();
                if (eventName === 'copy' || eventName === 'cut') {
                    window.logProtectedEvent('copy_attempt');
                }
                return false;
            }
        });
    });

    // 4. Block Keyboard Shortcuts
    document.addEventListener('keydown', function (e) {
        const key = (e.key || '').toLowerCase();
        
        const isScreenshotKey = e.key === 'PrintScreen' || key === 'printscreen' || (e.ctrlKey && e.shiftKey && key === 's') || (e.metaKey && e.shiftKey && key === 's');
        const isSavePrintCopy = (e.ctrlKey && ['s', 'p', 'u', 'c', 'x', 'a'].includes(key)) || (e.metaKey && ['s', 'p', 'u', 'c', 'x', 'a'].includes(key));
        const isDevTools = e.key === 'F12' || (e.ctrlKey && e.shiftKey && ['i', 'j', 'c'].includes(key)) || (e.metaKey && e.altKey && ['i', 'j', 'c'].includes(key));

        if (isScreenshotKey || isSavePrintCopy || isDevTools) {
            e.preventDefault();
            e.stopPropagation();
            
            let eventType = 'blocked_shortcut_' + key;
            if (isScreenshotKey) eventType = 'screenshot_key_attempt';
            else if (isDevTools) eventType = 'devtools_detected';
            else if (key === 'p') eventType = 'print_attempt';
            else if (key === 's') eventType = 'save_attempt';
            else if (key === 'c' || key === 'x') eventType = 'copy_attempt';

            window.logProtectedEvent(eventType);
            return false;
        }
    }, true);

    // Specific Handling for PrintScreen Keyup
    document.addEventListener('keyup', function (e) {
        if (e.key === 'PrintScreen' || (e.key || '').toLowerCase() === 'printscreen') {
            document.body.classList.add('protected-blur');

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText('Screenshot is disabled for protected Inspire College content.').catch(function () {});
            }

            window.logProtectedEvent('screenshot_key_attempt');

            setTimeout(function () {
                document.body.classList.remove('protected-blur');
            }, 2500);
        }
    });

    // 5. Disable print via JS
    window.addEventListener('beforeprint', function(e) {
        document.body.classList.add('protected-blur');
        window.logProtectedEvent('print_attempt');
    });
    window.addEventListener('afterprint', function(e) {
        document.body.classList.remove('protected-blur');
    });

    // 7. Add Blur/Hide Content When Tab Is Inactive
    window.addEventListener('blur', function () {
        document.body.classList.add('protected-blur');
        window.logProtectedEvent('window_blur_protected');
    });

    window.addEventListener('focus', function () {
        setTimeout(function () {
            document.body.classList.remove('protected-blur');
        }, 500);
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            document.body.classList.add('protected-blur');
            window.logProtectedEvent('visibility_hidden_protected');
        } else {
            setTimeout(function () {
                document.body.classList.remove('protected-blur');
            }, 500);
        }
    });

    // 8. Basic DevTools Detection
    setInterval(function () {
        const threshold = 160;
        const devtoolsOpen = 
            window.outerWidth - window.innerWidth > threshold ||
            window.outerHeight - window.innerHeight > threshold;

        if (devtoolsOpen) {
            document.body.classList.add('devtools-detected');
            window.logProtectedEvent('devtools_detected');
        } else {
            document.body.classList.remove('devtools-detected');
        }
    }, 1000);
})();
</script>

<!-- Content wrapper to apply positioning for watermarks -->
<div style="position: relative; overflow: hidden; width: 100%; background: #ffffff;" class="protected-content-wrapper">
    
    <!-- Watermark Elements (Subtle text only) -->
    <div class="protected-watermark-text">
        @for ($i = 0; $i < 40; $i++)
            <span>{{ $learnerEmail }} • Inspire College</span>
        @endfor
    </div>

    <!-- Fixed Bottom Right Badge -->
    <div class="protected-security-badge">
        Protected content • {{ $learnerEmail }}
    </div>

    <!-- Warnings overlays -->
    <div class="blur-warning">Protected content is hidden while this window is inactive.</div>
    <div class="devtools-warning">
        Protected course content is hidden while developer tools are open.<br>
        Please close developer tools to continue viewing this lesson.
    </div>

    <!-- 14. Screenshot Deterrent Message (Top) -->
    <div class="text-xs text-center text-slate-400 font-medium py-2 mb-4 border-b border-slate-100 uppercase tracking-wider relative z-10">
        This content is protected and watermarked for your account. Screenshots, copying, printing, recording, OCR extraction, and redistribution are not permitted.
    </div>

    <!-- Actual Slot Content (Wrapped in protected-content) -->
    <div class="protected-content relative z-10">
        {{ $slot }}
    </div>

    <!-- 14. Screenshot Deterrent Message (Bottom) -->
    <div class="text-xs text-center text-slate-400 font-medium py-2 mt-8 border-t border-slate-100 uppercase tracking-wider relative z-10">
        Protected for {{ $learnerName }} — {{ $learnerEmail }}
    </div>
</div>
