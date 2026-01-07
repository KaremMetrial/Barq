<?php

namespace Modules\ContactUs\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use Modules\ContactUs\Services\ContactUsService;
use Modules\ContactUs\Http\Resources\ContactUsResource;
use Modules\ContactUs\Http\Requests\CreateContactUsRequest;
use Modules\ContactUs\Http\Requests\UpdateContactUsRequest;
use Modules\ContactUs\Models\ContactUs;

class ContactUsController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(private ContactUsService $contactUsService) {}

    /**
     * Display a listing of the contact us messages.
     */
    public function index()
    {
        $this->authorize('viewAny', ContactUs::class);
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
        // Contact form submissions are public - no authorization needed
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
        $this->authorize('view', $contactUsMessage);
        return $this->successResponse([
            'contact_us' => new ContactUsResource($contactUsMessage),
        ], __('message.success'));
    }

    /**
     * Update the specified contact us message in storage.
     */
    public function update(UpdateContactUsRequest $request, $id)
    {
        $contactUsMessage = $this->contactUsService->getContactUsById($id);
        $this->authorize('update', $contactUsMessage);
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
        $contactUsMessage = $this->contactUsService->getContactUsById($id);
        $this->authorize('delete', $contactUsMessage);
        $this->contactUsService->deleteContactUs($id);
        return $this->successResponse(null, __('message.success'));
    }
}
