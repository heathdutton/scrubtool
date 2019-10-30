<?php

namespace App\Http\Controllers;

use App\File;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redirect;

class FileController extends Controller
{

    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $files = File::findByCurrentUser($request);

        return view('files')->with(['files' => collect($files)]);
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
     * @param  Request  $request
     */
    public function update(Request $request)
    {
        $tmp = 1;
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
                $file->createAndMove($uploadedFile, File::MODE_HASH, $request);
                $filesUploaded[] = $uploadedFile->getClientOriginalName();
            } catch (Exception $e) {
                $errors[$uploadedFile->getClientOriginalName()] = $e->getMessage();
            }
        }

        return response()->json([
            'success' => $filesUploaded,
            'errors'  => $errors,
        ]);
    }
}
