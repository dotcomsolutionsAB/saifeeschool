<?php

namespace App\Http\Controllers;
use App\Models\TeacherApplicationModel;
use App\Models\UploadModel;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Http\Request;

class TeacherApplicationController extends Controller
{
    //
    public function register(Request $request)
    {
        // Validate request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'dob' => 'nullable|date',
            'contact_number' => 'nullable|string|max:15|unique:t_teacher_application',
            'email' => 'nullable|string|email|max:255|unique:t_teacher_application',
            'apply_for' => 'nullable|string|max:100',
            'qualification' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'photo' => 'nullable|file|mimes:jpeg,jpg,png|max:2048',
            'id_proof' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
            'qualification_docs' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'status' => 'nullable|in:Active,Inactive',
            'remarks' => 'nullable|string',
        ]);

        // Handle file uploads
        $uploadedFiles = [
            'resume_path' => $this->uploadFile($request->file('resume')),
            'photo_path' => $this->uploadFile($request->file('photo')),
            'id_proof_path' => $this->uploadFile($request->file('id_proof')),
            'qualification_docs_path' => $this->uploadFile($request->file('qualification_docs')),
        ];

        // Merge file IDs into the data
        $applicationData = array_merge(
            $validated,
            [
                'resume_path' => $uploadedFiles['resume_path'],
                'photo_path' => $uploadedFiles['photo_path'],
                'id_proof_path' => $uploadedFiles['id_proof_path'],
                'qualification_docs_path' => $uploadedFiles['qualification_docs_path'],
            ]
        );

        // Save the application
        $application = TeacherApplicationModel::create($applicationData);

        return response()->json([
            'success' => true,
            'message' => 'Teacher application created successfully',
            'data' => $application->makeHidden(['id', 'created_at', 'updated_at']),
        ]);
    }

    private function uploadFile($file)
    {
        if ($file) {
            try {
                // Store the file
                $path = $file->store('uploads', 'public');
                $fileSize = $file->getSize();
                $fileExtension = $file->getClientOriginalExtension();
                $fileName = $file->getClientOriginalName();

                // Save the file details to the UploadModel
                $fileUpload = UploadModel::create([
                    'file_name' => $fileName,
                    'file_ext' => $fileExtension,
                    'file_url' => '/storage/' . $path,
                    'file_size' => $fileSize,
                ]);

                // Return the file ID
                return $fileUpload->id;
            } catch (\Exception $e) {
                // Log and return null if the upload fails
                \Log::error('File upload failed: ' . $e->getMessage());
                return null;
            }
        }

        return null;
    }


    public function view()
    {
        $applications = TeacherApplicationModel::with(['resume', 'photo', 'idProof', 'qualificationDocs'])
            ->get()
            ->map(function ($application) {
                return [
                    'id' => $application->id,
                    'name' => $application->name,
                    'gender' => $application->gender,
                    'dob' => $application->dob,
                    'contact_number' => $application->contact_number,
                    'email' => $application->email,
                    'apply_for' => $application->apply_for,
                    'qualification' => $application->qualification,
                    'experience_years' => $application->experience_years,
                    'address_line1' => $application->address_line1,
                    'address_line2' => $application->address_line2,
                    'city' => $application->city,
                    'state' => $application->state,
                    'country' => $application->country,
                    'pincode' => $application->pincode,
                    'status' => $application->status,
                    'remarks' => $application->remarks,
                    'resume_path' => $application->resume->file_url ?? null,
                    'photo_path' => $application->photo->file_url ?? null,
                    'id_proof_path' => $application->idProof->file_url ?? null,
                    'qualification_docs_path' => $application->qualificationDocs->file_url ?? null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $applications,
        ]);
    }



    public function update(Request $request, $id)
    {
        $application = TeacherApplicationModel::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'dob' => 'nullable|date',
            'contact_number' => 'nullable|string|max:15|unique:t_teacher_application,contact_number,' . $application->id,
            'email' => 'nullable|string|email|max:255|unique:t_teacher_application,email,' . $application->id,
            'apply_for' => 'nullable|string|max:100',
            'qualification' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'resume_path' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'photo_path' => 'nullable|file|mimes:jpeg,jpg,png|max:2048',
            'id_proof_path' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
            'qualification_docs_path' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'status' => 'nullable|in:Active,Inactive',
            'remarks' => 'nullable|string',
        ]);

        // Handle file uploads and replace existing ones
        $uploadedFiles = [];
        if ($request->hasFile('resume_path')) {
            $uploadedFiles['resume_path'] = $this->uploadFiles($request->file('resume_path'), $application->resume_path);
        }
        if ($request->hasFile('photo_path')) {
            $uploadedFiles['photo_path'] = $this->uploadFiles($request->file('photo_path'), $application->photo_path);
        }
        if ($request->hasFile('id_proof_path')) {
            $uploadedFiles['id_proof_path'] = $this->uploadFiles($request->file('id_proof_path'), $application->id_proof_path);
        }
        if ($request->hasFile('qualification_docs_path')) {
            $uploadedFiles['qualification_docs_path'] = $this->uploadFiles($request->file('qualification_docs_path'), $application->qualification_docs_path);
        }

        // Update the teacher application
        $application->update(array_merge($validated, $uploadedFiles));

        return response()->json([
            'success' => true,
            'message' => 'Teacher application updated successfully',
            'data' => $application,
        ]);
    }

    private function uploadFiles($file, $existingFileId = null)
    {
        if ($file) {
            // Delete the old file if it exists and is not in use
            if ($existingFileId) {
                $this->deleteFileIfUnused($existingFileId);
            }
    
            // Store the new file
            $path = $file->store('uploads', 'public');
            $fileSize = $file->getSize();
            $fileExtension = $file->getClientOriginalExtension();
            $fileName = $file->getClientOriginalName();
    
            // Save file record in the uploads table
            $fileUpload = UploadModel::create([
                'file_name' => $fileName,
                'file_ext' => $fileExtension,
                'file_url' => $path,
                'file_size' => $fileSize,
            ]);
    
            // Return the file ID
            return $fileUpload->id;
        }
    
        return null;
    }

    private function deleteFileIfUnused($fileId)
    {
        $file = UploadModel::find($fileId);

        if ($file) {
            // Check if the file path is being used elsewhere in the Uploads table
            $isFileInUse = UploadModel::where('file_url', $file->file_url)->where('id', '!=', $fileId)->exists();

            print_r($isFileInUse);
            if (!$isFileInUse) {
                // File is not used anywhere else, delete from storage
                if (\Storage::disk('public')->exists($file->file_url)) {
                    \Storage::disk('public')->delete($file->file_url);
                }
            }

            // Finally, delete the record from the database
            $file->delete();
        }
    }


}
