<?php

namespace Modules\ContactUs\Services;

use Illuminate\Support\Facades\DB;
use Modules\ContactUs\Models\ContactUs;
use Illuminate\Database\Eloquent\Collection;
use Modules\ContactUs\Repositories\ContactUsRepository;

class ContactUsService
{
    public function __construct(
        protected ContactUsRepository $ContactUsRepository
    ) {}

    public function getAllContactUss($filters = [])
    {
        return $this->ContactUsRepository->paginate($filters);
    }

    public function createContactUs(array $data): ?ContactUs
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->ContactUsRepository->create($data);
        });
    }

    public function getContactUsById(int $id): ?ContactUs
    {
        return $this->ContactUsRepository->find($id);
    }

    public function updateContactUs(int $id, array $data): ?ContactUs
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->ContactUsRepository->update($id, $data);
        });
    }

    public function deleteContactUs(int $id): bool
    {
        return $this->ContactUsRepository->delete($id);
    }
}
