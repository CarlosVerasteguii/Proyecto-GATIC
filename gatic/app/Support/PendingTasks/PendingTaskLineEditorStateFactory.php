<?php

declare(strict_types=1);

namespace App\Support\PendingTasks;

use App\Models\PendingTaskLine;

final class PendingTaskLineEditorStateFactory
{
    /**
     * @return array{
     *     lineType: string,
     *     productId: int|null,
     *     serial: string,
     *     assetTag: string,
     *     quantity: string|null,
     *     serializedBulkInput: string,
     *     serializedBulkPreview: array<int, array{line: int, value: string, status: string, status_label: string, message: string|null}>,
     *     serializedBulkCount: int,
     *     serializedBulkOkCount: int,
     *     serializedBulkDuplicateCount: int,
     *     serializedBulkInvalidCount: int,
     *     serializedBulkLimitError: string|null,
     *     employeeId: int|null,
     *     note: string,
     *     editingLineId: int|null
     * }
     */
    public function resetDraftEditor(): array
    {
        return [
            'lineType' => '',
            'productId' => null,
            'serial' => '',
            'assetTag' => '',
            'quantity' => null,
            'serializedBulkInput' => '',
            'serializedBulkPreview' => [],
            'serializedBulkCount' => 0,
            'serializedBulkOkCount' => 0,
            'serializedBulkDuplicateCount' => 0,
            'serializedBulkInvalidCount' => 0,
            'serializedBulkLimitError' => null,
            'employeeId' => null,
            'note' => '',
            'editingLineId' => null,
        ];
    }

    /**
     * @return array{
     *     editingLineId: int,
     *     lineType: string,
     *     productId: int,
     *     serial: string,
     *     assetTag: string,
     *     quantity: string|null,
     *     employeeId: int,
     *     note: string
     * }
     */
    public function hydrateDraftEditor(PendingTaskLine $line): array
    {
        return [
            'editingLineId' => $line->id,
            'lineType' => $line->line_type->value,
            'productId' => $line->product_id,
            'serial' => $line->serial ?? '',
            'assetTag' => $line->asset_tag ?? '',
            'quantity' => $line->quantity !== null ? (string) $line->quantity : null,
            'employeeId' => $line->employee_id,
            'note' => $line->note,
        ];
    }

    /**
     * @return array{
     *     editingProcessLineId: int|null,
     *     processLineSerial: string,
     *     processLineAssetTag: string,
     *     processLineQuantity: string|null,
     *     processLineEmployeeId: int|null,
     *     processLineNote: string
     * }
     */
    public function resetProcessEditor(): array
    {
        return [
            'editingProcessLineId' => null,
            'processLineSerial' => '',
            'processLineAssetTag' => '',
            'processLineQuantity' => null,
            'processLineEmployeeId' => null,
            'processLineNote' => '',
        ];
    }

    /**
     * @return array{
     *     editingProcessLineId: int,
     *     processLineSerial: string,
     *     processLineAssetTag: string,
     *     processLineQuantity: string|null,
     *     processLineEmployeeId: int,
     *     processLineNote: string
     * }
     */
    public function hydrateProcessEditor(PendingTaskLine $line): array
    {
        return [
            'editingProcessLineId' => $line->id,
            'processLineSerial' => $line->serial ?? '',
            'processLineAssetTag' => $line->asset_tag ?? '',
            'processLineQuantity' => $line->quantity !== null ? (string) $line->quantity : null,
            'processLineEmployeeId' => $line->employee_id,
            'processLineNote' => $line->note,
        ];
    }
}
