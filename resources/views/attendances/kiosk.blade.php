@extends('layouts.master')

@section('title')
    {{ trans('attendances.kiosk_page') }}
@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 style="margin:0;">{{ trans('attendances.kiosk_page') }}</h5>
                <small class="text-muted">{{ trans('attendances.kiosk_hint') }}</small>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label">{{ trans('attendances.member_code') }}</label>
                    <input id="kiosk_member_code" type="text" class="form-control form-control-lg"
                           autocomplete="off" autofocus>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="kiosk_deduct_pt" checked>
                    <label class="form-check-label" for="kiosk_deduct_pt">
                        {{ trans('attendances.deduct_pt_hint') }}
                    </label>
                </div>

                <button id="kiosk_btn" class="btn btn-success btn-lg">
                    {{ trans('attendances.checkin') }}
                </button>

                <hr>
                <div id="kiosk_result" class="alert alert-info" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('kiosk_member_code');
    const btn = document.getElementById('kiosk_btn');
    const resBox = document.getElementById('kiosk_result');
    const deductPt = document.getElementById('kiosk_deduct_pt');

    function csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

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

            o.onended = () => { try { ctx.close(); } catch (e) {} };
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

    async function send() {
        const code = (input.value || '').trim();
        if (!code) return;

        resBox.style.display = 'block';
        resBox.className = 'alert alert-info';
        resBox.innerText = '{{ trans('attendances.processing') }}';

        try {
            const r = await fetch('{{ route('attendances.actions.scan') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    member_code: code,
                    deduct_pt: deductPt.checked ? 1 : 0
                })
            });

            const j = await r.json();
            if (j.ok) {
                resBox.className = 'alert alert-success';
                resBox.innerText = j.message;
                playOkSound();
                input.value = '';
                input.focus();
            } else {
                resBox.className = 'alert alert-danger';
                resBox.innerText = j.message || 'Error';
                playFailSound();
                input.select();
            }
        } catch (e) {
            resBox.className = 'alert alert-danger';
            resBox.innerText = '{{ trans('attendances.ajax_error') }}';
            playFailSound();
        }
    }

    btn.addEventListener('click', send);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            send();
        }
    });
})();
</script>
@endsection
