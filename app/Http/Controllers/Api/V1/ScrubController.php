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

        $hashType   = null;
        $row        = [$record];
        $helper     = new FileAnalysisHelper();
        $columnType = $helper->getType($record, 0, true);

        if (!$columnType) {
            return response()->json([
                'record' => $record,
                'result' => 'error',
                'error'  => 'Could not discern the type of record provided.',
            ], 400);
        }

        // Assume this could be email or phone if a hash was sent
        if (is_array($columnType)) {
            [$columnType, $hashType] = $columnType;
        }
        $apiSuppressionListHelper = new ApiSuppressionListHelper(collect([$suppressionList]), $columnType, $hashType);
        $apiSuppressionListHelper->scrubRow($row);
        $scrubbed = (bool) !count($row);

        $result = [
            'record'          => $record,
            'type'            => __('column_types.'.$columnType),
            'result'          => $scrubbed ? 'Scrubbed' : 'Not Found',
            'status'          => $scrubbed ? 0 : 1,
            'suppressionList' => $suppressionList->name,
        ];
        $errors = $apiSuppressionListHelper->getErrors();
        if ($errors) {
            $result['error'] = implode(' ', $errors);
        }

        return response()->json($result, $scrubbed ? 406 : 200);
    }
}
