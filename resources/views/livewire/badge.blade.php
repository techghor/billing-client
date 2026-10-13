<span>
    @if (! empty($badge))
        @if ($badge['type'] === 'danger')
            <span class="ml-1 inline-flex items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-semibold text-white tracking-wide shrink-0">
                {{ $badge['label'] }}
            </span>
        @elseif ($badge['type'] === 'warning')
            <span class="ml-1 inline-flex items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-semibold text-white tracking-wide shrink-0">
                {{ $badge['label'] }}
            </span>
        @else
            <span class="ml-1 inline-flex items-center justify-center rounded-full bg-slate-500 px-1.5 py-0.5 text-[10px] font-semibold text-white tracking-wide shrink-0">
                {{ $badge['label'] }}
            </span>
        @endif
    @endif
</span>

