<script>
(function () {
    const SCAN_MIN_LEN = 6;
    const IDLE_RESET_MS = 250;      // لو توقف الإدخال أكتر من كده نعتبره كتابة عادية
    const TOAST_MS = 8000;          // مدة ظهور الرسالة
    const MAX_BUFFER = 80;

    let buffer = '';
    let lastTime = 0;
    let toastTimer = null;

    function csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function ensureToastBox() {
        let box = document.getElementById('barcode_scan_toast');
        if (!box) {
            box = document.createElement('div');
            box.id = 'barcode_scan_toast';
            box.style.position = 'fixed';
            box.style.bottom = '15px';
            box.style.right = '15px';
            box.style.zIndex = '99999';
            box.style.maxWidth = '520px';
            box.style.cursor = 'pointer';
            box.title = 'Click to close';
            box.addEventListener('click', function () {
                box.innerHTML = '';
            });
            document.body.appendChild(box);
        }
        return box;
    }

    function showToast(msg, ok) {
        const box = ensureToastBox();
        const bg = ok ? '#198754' : '#dc3545';

        box.innerHTML =
            '<div style="padding:12px 14px;border-radius:8px;color:#fff;background:' + bg + ';box-shadow:0 10px 25px rgba(0,0,0,.18);">' +
                '<div style="font-weight:700;margin-bottom:4px;">' + (ok ? '{{ trans('attendances.toast_ok') }}' : '{{ trans('attendances.toast_fail') }}') + '</div>' +
                '<div style="line-height:1.4;">' + (msg || '') + '</div>' +
                '<div style="margin-top:6px;font-size:12px;opacity:.9;">{{ trans('attendances.toast_hint_close') }}</div>' +
            '</div>';

        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { box.innerHTML = ''; }, TOAST_MS);
    }

    // ✅ Sounds (no audio files)
    function beep(freq, durationMs, type, volume, whenSec) {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;

            const ctx = new AudioCtx();
            const o = ctx.createOscillator();
            const g = ctx.createGain();

            o.type = type || 'sine';
            o.frequency.value = freq;

            g.gain.value = volume || 0.08;

            o.connect(g);
            g.connect(ctx.destination);

            const startAt = ctx.currentTime + (whenSec || 0);
            o.start(startAt);
            o.stop(startAt + (durationMs / 1000));

            o.onended = () => {
                try { ctx.close(); } catch (e) {}
            };
        } catch (e) {}
    }

    function playOkSound() {
        beep(880, 90, 'sine', 0.08, 0);
        beep(1320, 120, 'sine', 0.08, 0.10);
    }

    function playFailSound() {
        beep(220, 220, 'triangle', 0.10, 0);
        beep(180, 260, 'triangle', 0.10, 0.22);
    }

    async function sendScan(code) {
        try {
            const r = await fetch("{{ route('attendances.actions.scan') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ member_code: code, deduct_pt: 1 })
            });

            const j = await r.json();

            if (j && j.ok) {
                showToast(j.message || 'OK', true);
                playOkSound();
            } else {
                showToast((j && j.message) ? j.message : 'Error', false);
                playFailSound();
            }
        } catch (e) {
            showToast("{{ trans('attendances.ajax_error') }}", false);
            playFailSound();
        }
    }

    function resetBuffer() {
        buffer = '';
        lastTime = 0;
    }

    document.addEventListener('keydown', function (e) {
        // ignore modifier combos
        if (e.ctrlKey || e.altKey || e.metaKey) return;

        const now = Date.now();
        const diff = lastTime ? (now - lastTime) : 0;

        // if idle long => new sequence
        if (lastTime && diff > IDLE_RESET_MS) {
            buffer = '';
        }

        // Enter ends scan
        if (e.key === 'Enter') {
            if (buffer.length >= SCAN_MIN_LEN) {
                const code = buffer;
                resetBuffer();
                sendScan(code);
            } else {
                resetBuffer();
            }
            return;
        }

        // collect only printable chars
        if (e.key && e.key.length === 1) {
            // prevent buffer overflow
            if (buffer.length >= MAX_BUFFER) buffer = '';

            // if typing slow, start fresh (avoid capturing normal typing)
            if (lastTime && diff > 90) {
                buffer = '';
            }

            buffer += e.key;
            lastTime = now;
        } else {
            // non printable
            lastTime = now;
        }
    }, true);

})();
</script>
