<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiSuppressionListHelper;
use App\Helpers\FileAnalysisHelper;
use App\Http\Controllers\Controller;
use App\Models\SuppressionList;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScrubController extends Controller
{
    /**
     * @param $idToken
     * @param $record
     * @param  Request  $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function single($idToken, $record, Request $request)
    {
        if (!$idToken) {
            return response()->json([
                'record' => $record,
                'result' => 'error',
                'error'  => 'Suppression list token was not provided',
            ], 404);
        }

        /** @var SuppressionList $suppressionList */
        $suppressionList = SuppressionList::findByIdToken($idToken);
        if (!$suppressionList) {
            return response()->json([
                'record' => $record,
                'result' => 'error',
                'error'  => 'Suppression list token is not valid',
            ], 404);
        }

        $helper     = new FileAnalysisHelper();
        $columnType = $helper->getType($record);
        if (!$columnType) {
            return response()->json([
                'record' => $record,
                'result' => 'error',
                'error'  => 'Could not discern the type of record provided.',
            ], 400);
        }

        $apiSuppressionListHelper = new ApiSuppressionListHelper(collect([$suppressionList]), $columnType);
        $row                      = [$record];
        $apiSuppressionListHelper->scrubRow($row);
        $scrubbed = (bool) !count($row);

        return response()->json([
            'record'          => $record,
            'type'            => __('column_types.'.$columnType),
            'result'          => $scrubbed ? 'Scrubbed' : 'Not Found',
            'status'          => $scrubbed ? 0 : 1,
            'suppressionList' => $suppressionList->name,
        ], $scrubbed ? 406 : 200);
    }
}
