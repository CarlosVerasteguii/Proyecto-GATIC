<?php

namespace App\Actions\Suppliers;

use App\Models\Supplier;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

final class UpsertSupplier
{
    public const STATUS_CREATED = 'created';

    public const STATUS_EXISTING = 'existing';

    public const STATUS_TRASHED = 'trashed';

    /**
     * @param  array{name:string,contact?:?string,notes?:?string}  $data
     * @return array{status:string,supplier:Supplier}
     */
    public function execute(array $data): array
    {
        $normalizedName = Supplier::normalizeName($data['name']);
        if ($normalizedName === null) {
            throw new InvalidArgumentException('Supplier name cannot be empty.');
        }

        $contact = $this->normalizeOptionalText($data['contact'] ?? null);
        $notes = $this->normalizeOptionalText($data['notes'] ?? null);

        $softDeleted = Supplier::onlyTrashed()->where('name', $normalizedName)->first();
        if ($softDeleted !== null) {
            return [
                'status' => self::STATUS_TRASHED,
                'supplier' => $softDeleted,
            ];
        }

        $existing = Supplier::query()->where('name', $normalizedName)->first();
        if ($existing !== null) {
            return [
                'status' => self::STATUS_EXISTING,
                'supplier' => $existing,
            ];
        }

        try {
            $supplier = Supplier::query()->create([
                'name' => $normalizedName,
                'contact' => $contact,
                'notes' => $notes,
            ]);
        } catch (QueryException $exception) {
            if (! $this->isDuplicateNameException($exception)) {
                throw $exception;
            }

            $existingAfter = Supplier::query()->where('name', $normalizedName)->first();
            if ($existingAfter !== null) {
                return [
                    'status' => self::STATUS_EXISTING,
                    'supplier' => $existingAfter,
                ];
            }

            $trashedAfter = Supplier::onlyTrashed()->where('name', $normalizedName)->first();
            if ($trashedAfter !== null) {
                return [
                    'status' => self::STATUS_TRASHED,
                    'supplier' => $trashedAfter,
                ];
            }

            throw $exception;
        }

        return [
            'status' => self::STATUS_CREATED,
            'supplier' => $supplier,
        ];
    }

    private function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\\s+/u', ' ', trim($value));

        if (! is_string($normalized)) {
            return null;
        }

        return $normalized === '' ? null : $normalized;
    }

    private function isDuplicateNameException(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo;

        if (! is_array($errorInfo) || count($errorInfo) < 2) {
            return false;
        }

        $driverCode = (int) ($errorInfo[1] ?? 0);

        return $driverCode === 1062;
    }
}
