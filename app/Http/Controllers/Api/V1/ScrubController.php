<?php

namespace App\Http\Api\V1\Controllers;

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
    public function share($idToken, $record, Request $request)
    {
        if (!$idToken) {
            return response()->json([
                'result' => 'error',
                'error'  => 'Suppression list token was not provided',
            ]);
        }

        /** @var SuppressionList $suppressionList */
        $suppressionList = SuppressionList::findByIdToken($idToken);
        if (!$suppressionList) {
            return response()->json([
                'result' => 'error',
                'error'  => 'Suppression list token is not valid',
            ]);
        }

        $helper = new FileAnalysisHelper();
        $type   = $helper->getType($record);
        if (!$type) {
            return response()->json([
                'result' => 'error',
                'error'  => 'Could not discern the type of record provided.',
            ]);
        }
        $apiSuppressionListHelper = new ApiSuppressionListHelper(collect($suppressionList), $type);
        $row                      = [$record];
        $apiSuppressionListHelper->scrubRow($row);
        $scrubbed = (bool) !count($row);

        return response()->json([
            'result' => $scrubbed ? 'scrubbed' : 'not found',
        ]);
    }
}
