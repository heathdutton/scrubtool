<?php

namespace App\Http\Controllers;

use App\File;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class HashFileController extends Controller
{
    public function index()
    {
        return view('hash');
    }

    /**
     * upload a file and associate it to the db while copying it to a persistent location.
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function upload(Request $request)
    {
        $filesUploaded = [];

        /** @var UploadedFile $uploadedFile */
        foreach ($request->allFiles() as $uploadedFile) {
            if (!($uploadedFile instanceof UploadedFile)) {
                throw new Exception('Unable to parse file upload.');
            }

            $fileModel = new File();
            /** @var File $file */
            $file = $fileModel::create([
                'name'                 => $uploadedFile->getClientOriginalName(),
                'location'             => $uploadedFile->getRealPath(),
                'user_id'              => $request->user() ? $request->user()->id : null,
                'list_id'              => null,
                'ip_address'           => $request->getClientIp(),
                'session_id'           => $request->getSession()->getId(),
                'type'                 => File::TYPE_HASH,
                'format'               => null,
                'columns'              => null,
                'column_count'         => 0,
                'size'                 => $uploadedFile->getSize(),
                'rows_total'           => 0,
                'rows_processed'       => 0,
                'rows_scrubbed'        => 0,
                'rows_invalid'         => 0,
                'rows_email_valid'     => 0,
                'rows_email_invalid'   => 0,
                'rows_email_duplicate' => 0,
                'rows_email_dnc'       => 0,
                'rows_phone_valid'     => 0,
                'rows_phone_invalid'   => 0,
                'rows_phone_duplicate' => 0,
                'rows_phone_dnc'       => 0,
            ]);
            $file->move($uploadedFile);
            $filesUploaded[] = $uploadedFile->getClientOriginalName();
        }

        return response()->json(['success' => $filesUploaded]);
    }
}
