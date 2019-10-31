<?php

namespace App\Http\Controllers;

use App\File;
use App\Forms\FileForm;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redirect;
use Kris\LaravelFormBuilder\FormBuilder;

class FileController extends Controller
{

    /**
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, FormBuilder $formBuilder)
    {
        $files = File::findByCurrentUser($request);

        /** @var File $file */
        foreach ($files as $file) {
            $file->form = $file->buildForm($formBuilder);
        }

        return view('files')->with([
            'files' => collect($files),
        ]);
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
     * @param  FormBuilder  $formBuilder
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(FormBuilder $formBuilder)
    {
        /** @var FileForm $form */
        $form = $formBuilder->create(FileForm::class);

        if (!$form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }
        $values = $form->getFieldValues();

        // @todo - Update record
    }

    // /**
    //  * @param  FormBuilder  $formBuilder
    //  *
    //  * @return \Illuminate\Http\RedirectResponse
    //  */
    // public function update(FormBuilder $formBuilder)
    // {
    //     $form = $formBuilder->create(FileForm::class, [
    //         'method' => 'POST',
    //         'url'    => route('fileupdate'),
    //     ]);
    //
    //     return view('fileupdate', compact('form'));
    // }

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
