@php
    use Filament\Support\Facades\FilamentView;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $filters = $this->getFilters();
    $categoryBreakdown = $this->getCategoryBreakdown();
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section :description="$description" :heading="$heading">
        @if ($filters)
            <x-slot name="headerEnd">
                <x-filament::input.wrapper
                    inline-prefix
                    wire:target="filter"
                    class="w-max sm:-my-2"
                >
                    <x-filament::input.select
                        inline-prefix
                        wire:model.live="filter"
                    >
                        @foreach ($filters as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-slot>
        @endif

        <div
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}="updateChartData"
            @endif
        >
            <div
                @if (FilamentView::hasSpaMode())
                    x-load="visible"
                @else
                    x-load
                @endif
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            options: @js($this->getOptions()),
                            type: @js($this->getType()),
                        })"
                @class([
                    match ($color) {
                        'gray' => null,
                        default => 'fi-color-custom',
                    },
                    is_string($color) ? "fi-color-{$color}" : null,
                ])
            >
                <canvas
                    x-ref="canvas"
                    @if ($maxHeight = $this->getMaxHeight())
                        style="max-height: {{ $maxHeight }}"
                    @endif
                ></canvas>

                <span
                    x-ref="backgroundColorElement"
                    @class([
                        match ($color) {
                            'gray' => 'text-gray-100 dark:text-gray-800',
                            default => 'text-custom-50 dark:text-custom-400/10',
                        },
                    ])
                    @style([
                        \Filament\Support\get_color_css_variables(
                            $color,
                            shades: [50, 400],
                            alias: 'widgets::chart-widget.background',
                        ) => $color !== 'gray',
                    ])
                ></span>

                <span
                    x-ref="borderColorElement"
                    @class([
                        match ($color) {
                            'gray' => 'text-gray-400',
                            default => 'text-custom-500 dark:text-custom-400',
                        },
                    ])
                    @style([
                        \Filament\Support\get_color_css_variables(
                            $color,
                            shades: [400, 500],
                            alias: 'widgets::chart-widget.border',
                        ) => $color !== 'gray',
                    ])
                ></span>

                <span
                    x-ref="gridColorElement"
                    class="text-gray-200 dark:text-gray-800"
                ></span>

                <span
                    x-ref="textColorElement"
                    class="text-gray-500 dark:text-gray-400"
                ></span>
            </div>
        </div>

        {{-- Category Breakdown Share Cards --}}
        @if(!empty($categoryBreakdown))
            <div class="feed-category-section mt-8 pt-6 border-t border-slate-200/80 dark:border-slate-800/80">
                <div class="feed-category-section-header mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-teal-500 shadow-sm shadow-teal-500/50"></span>
                        <h4 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                            {{ __('Feed Category Stock & Dispatch Turnover') }}
                        </h4>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-700 dark:bg-teal-950/70 dark:text-teal-300 border border-teal-200/80 dark:border-teal-800/60 shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse"></span>
                        {{ __('Live Godown Status') }}
                    </span>
                </div>

                <div class="feed-category-grid">
                    @foreach($categoryBreakdown as $cat)
                        <div class="feed-category-card">
                            <div class="feed-category-header">
                                <span class="feed-category-name" title="{{ $cat['name'] }}">
                                    {{ $cat['name'] }}
                                </span>
                            </div>

                            <div class="feed-category-body">
                                <div class="feed-category-stat-row">
                                    <div class="feed-category-bags-wrap">
                                        <span class="feed-category-bags-val">
                                            {{ $cat['bags'] }}
                                        </span>
                                        <span class="feed-category-bags-unit">
                                            {{ __('Bags') }}
                                        </span>
                                    </div>
                                    <span class="feed-category-badge">
                                        {{ $cat['share'] }}%
                                    </span>
                                </div>

                                <div class="feed-category-bar-track">
                                    <div class="feed-category-bar-fill" style="width: {{ max(4, min(100, (int) $cat['share'])) }}%;"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
