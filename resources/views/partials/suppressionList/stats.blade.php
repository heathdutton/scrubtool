@include('partials.stat', ['icon' => 'align-left', 'value' => $suppressionList->statParent('rows_imported') ?? 0, 'label' => __('Total Records')])
{{--                ->where('status', \App\Models\SuppressionListSupport::STATUS_READY)--}}
@foreach($suppressionList->suppressionListSupports->sortBy('id')->unique('column_type') as $support)
    @if($support->count)
        @include('partials.stat', [
            'icon' => __("column_types.icons.{$support->column_type}"),
            'value' => number_format($support->count),
            'label' => __('Unique :type', ['type' => __('column_types.plural.'.$support->column_type)]),
        ])
    @endif
@endforeach
@include('partials.stat', ['icon' => 'filter', 'value' => $suppressionList->statParent('rows_scrubbed') ?? 0, 'label' => __('Scrubbed Records')])
