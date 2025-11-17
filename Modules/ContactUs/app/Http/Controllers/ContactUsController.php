<?php

namespace Modules\ContactUs\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use Modules\ContactUs\Services\ContactUsService;
use Modules\ContactUs\Http\Resources\ContactUsResource;
use Modules\ContactUs\Http\Requests\CreateContactUsRequest;
use Modules\ContactUs\Http\Requests\UpdateContactUsRequest;

class ContactUsController extends Controller
{
    use ApiResponse;

    public function __construct(private ContactUsService $contactUsService) {}

    /**
     * Display a listing of the contact us messages.
     */
    public function index()
    {
        $contactUsMessages = $this->contactUsService->getAllContactUss();
        return $this->successResponse([
            'contact_us' => ContactUsResource::collection($contactUsMessages),
            "pagination" => new PaginationResource($contactUsMessages),
        ], __('message.success'));
    }

    /**
     * Store a newly created contact us message in storage.
     */
    public function store(CreateContactUsRequest $request)
    {
        $contactUs = $this->contactUsService->createContactUs($request->validated());
        return $this->successResponse([
            'contact_us' => new ContactUsResource($contactUs),
        ], __('message.success'));
    }

    /**
     * Display the specified contact us message.
     */
    public function show($id)
    {
        $contactUsMessage = $this->contactUsService->getContactUsById($id);
        return $this->successResponse([
            'contact_us' => new ContactUsResource($contactUsMessage),
        ], __('message.success'));
    }

    /**
     * Update the specified contact us message in storage.
     */
    public function update(UpdateContactUsRequest $request, $id)
    {
        $contactUsMessage = $this->contactUsService->updateContactUs($id, $request->validated());
        return $this->successResponse([
            'contact_us' => new ContactUsResource($contactUsMessage),
        ], __('message.success'));
    }

    /**
     * Remove the specified contact us message from storage.
     */
    public function destroy($id)
    {
        $this->contactUsService->deleteContactUs($id);
        return $this->successResponse(null, __('message.success'));
    }
}
