<?php

namespace App\Http\Controllers;

use App\File;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Excel;

class FilesController extends Controller
{
    private $excel;

    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }

    public function index()
    {
        return view('files');
    }

    /**
     * Later can be a page for the results of a single file upload.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function file()
    {
        return Redirect::to('files', 301);
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
        $errors = $filesUploaded = $stats = [];

        /** @var UploadedFile $uploadedFile */
        foreach ($request->allFiles() as $uploadedFile) {
            $file = new File();
            try {
                $fileName        = $uploadedFile->getClientOriginalName();
                $file            = $file->createAndMove($uploadedFile, File::MODE_HASH, $request);
                $filesUploaded[] = $fileName;
                $stats[]         = $file->getAttributesForOutput();
            } catch (Exception $e) {
                $errors[$uploadedFile->getClientOriginalName()] = $e->getMessage();
            }
        }

        return response()->json([
            'success' => $filesUploaded, 'errors' => $errors, 'stats' => $stats,
        ]);
    }
}
