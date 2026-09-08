{{--
    Stepper Progress Tracker — 5 Tahapan (Parallel Workflow)
    Usage: @include('partials.stepper', ['steps' => $tiket->getStepperData()])

    Each step: ['name', 'icon', 'class', 'label', 'sublabel']
    class dari model: text-success (selesai), text-warning (proses/revisi), text-secondary (menunggu).
    Fallback pendukung: completed / active / revisi / pending.
--}}
@php
    $steps = $steps ?? [];
    $count = count($steps);
@endphp

@if($count > 0)
<div class="card card-custom p-3 mb-4 shadow-sm">
    <div class="stepper-tracker">
        <div class="d-flex align-items-start justify-content-between position-relative">
            {{-- Garis penghubung (lurus di belakang lingkaran) --}}
            <div class="position-absolute w-100" style="top: 22px; left: 0; right: 0; height: 3px; background: #dee2e6; z-index: 0;"></div>

            @foreach($steps as $i => $step)
                @php
                    $cls = $step['class'] ?? 'text-secondary';
                    // Deteksi state: model returns text-success / text-warning / text-secondary,
                    // namun tetap dukung completed / active / revisi / pending sebagai fallback.
                    $isCompleted = str_contains($cls, 'success') || str_contains($cls, 'completed');
                    $isRevisi    = str_contains($cls, 'warning') && str_contains($cls, 'revisi');
                    $isLoading   = str_contains($cls, 'warning') || str_contains($cls, 'active') || str_contains($cls, 'revisi');
                    $isPending   = !$isCompleted && !$isLoading;

                    if ($isCompleted) {
                        $circleBg = 'bg-success text-white';
                        $icon = 'bi-check-lg';
                        $labelClass = 'text-success';
                    } elseif ($isLoading) {
                        $circleBg = 'bg-warning text-dark shadow';
                        $icon = $isRevisi ? 'bi-arrow-repeat' : ($step['icon'] ?? 'bi-circle');
                        $labelClass = 'text-warning';
                    } else {
                        $circleBg = 'bg-light border text-muted';
                        $icon = $step['icon'] ?? 'bi-circle';
                        $labelClass = 'text-muted';
                    }
                @endphp

                <div class="d-flex flex-column align-items-center position-relative" style="flex: 1; z-index: 1;">
                    {{-- Lingkaran --}}
                    <div class="rounded-circle d-flex align-items-center justify-content-center {{ $circleBg }} stepper-circle"
                         style="width: 44px; height: 44px; border: 3px solid #fff; box-shadow: 0 0 0 2px #dee2e6;"
                         title="{{ $step['name'] }}{{ !empty($step['label']) ? ' - ' . $step['label'] : '' }}">
                        <i class="bi {{ $icon }} fs-5"></i>
                    </div>

                    {{-- Label --}}
                    <div class="text-center mt-2" style="max-width: 96px;">
                        <div class="fw-bold {{ $labelClass }}" style="font-size: 0.75rem;">
                            {{ $step['name'] }}
                        </div>
                        @if(!empty($step['label']))
                            <div class="text-muted" style="font-size: 0.65rem; line-height: 1.3;">
                                {{ $step['label'] }}
                            </div>
                        @endif
                        @if(!empty($step['sublabel']))
                            <span class="badge bg-{{ $isLoading ? 'warning text-dark' : 'secondary' }} mt-1" style="font-size: 0.6rem;">
                                {{ $step['sublabel'] }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@once
<style>
    .stepper-tracker .stepper-circle {
        transition: all 0.25s ease;
    }
    .stepper-tracker .stepper-circle:hover {
        transform: scale(1.08);
    }
    @media (max-width: 767.98px) {
        .stepper-tracker .d-flex {
            flex-direction: column !important;
            gap: 1rem;
        }
        .stepper-tracker .position-absolute {
            display: none !important;
        }
        .stepper-tracker .d-flex > div {
            flex-direction: row !important;
            gap: 0.75rem;
            max-width: 100% !important;
        }
        .stepper-tracker .d-flex > div .text-center {
            text-align: left !important;
        }
    }
</style>
@endonce
@endif