<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Maatwebsite\Excel\Excel;
use Maatwebsite\LaravelNovaExcel\Actions\DownloadExcel;

class File extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\File::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'name',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  Request  $request
     *
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('name')->sortable(),
            BelongsTo::make('user'),
            Text::make('ip_address'),
            Text::make('country'),
            Text::make('md5'),
            DateTime::make('updated_at'),
            Number::make('mode'),
            Number::make('status'),
            Number::make('column_count'),
            Number::make('size')->sortable(),
            Boolean::make('global')->sortable(),
            Boolean::make('private')->sortable(),
            Text::make('message'),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  Request  $request
     *
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  Request  $request
     *
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  Request  $request
     *
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  Request  $request
     *
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            (new DownloadExcel)
                ->withName(__('Export'))
                ->withWriterType(Excel::CSV)
                ->withHeadings()
                ->withFilename('files-'.time().'.csv'),
        ];
    }

}
