<?php

namespace App\Http\Controllers;

use App\File;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Kris\LaravelFormBuilder\FormBuilder;
use Kris\LaravelFormBuilder\FormBuilderTrait;

class FileController extends Controller
{

    use FormBuilderTrait;

    /**
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, FormBuilder $formBuilder)
    {
        $files = File::findByCurrentUser($request, $formBuilder);

        return view('files')->with([
            'files'  => collect($files),
            'upload' => true,
        ]);
    }

    /**
     * Later can be a page for the results of a single file upload.
     *
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function file(Request $request, FormBuilder $formBuilder)
    {
        if (empty($request->id) || (int) $request->id < 1) {
            return redirect()->back();
        }

        /** @var File $file */
        $file = File::findByCurrentUser($request, $formBuilder, (int) $request->id, 1)->first();

        return view('files')->with([
            'files'  => [$file],
            'upload' => false,
        ]);
    }

    public function download(Request $request)
    {
        if (empty($request->id) || (int) $request->id < 1) {
            return redirect()->back();
        }

        /** @var File $file */
        $file = File::findByCurrentUser($request, null, (int) $request->id, 1)->first();

        return $file->download();
    }

    /**
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, FormBuilder $formBuilder)
    {
        if (empty($request->id) || (int) $request->id < 1) {
            return redirect()->back();
        }

        /** @var File $file */
        $file = File::findByCurrentUser($request, $formBuilder, (int) $request->id)->first();

        if (!$file || $file->status ^ File::STATUS_INPUT_NEEDED) {
            return redirect()->back();
        }

        if (!$file->form->isValid()) {
            return redirect()->back()->withErrors($file->form->getErrors())->withInput();
        }

        $file->saveInputSettings($file->form->getFieldValues());

        return redirect('files/'.$file->id);
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
