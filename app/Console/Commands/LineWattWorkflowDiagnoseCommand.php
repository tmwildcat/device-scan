<?php

namespace App\Console\Commands;

use App\Models\CompiledDeviceRecord;
use App\Models\Notification;
use Illuminate\Console\Command;

class LineWattWorkflowDiagnoseCommand extends Command
{
    protected $signature = 'linewatt:workflow-diagnose {record_id}';

    protected $description = 'Print LineWatt publishing workflow state for a compiled Engineering Record.';

    public function handle(): int
    {
        $record = CompiledDeviceRecord::query()
            ->with(['datasheet', 'reviewComments.actor'])
            ->where('id', $this->argument('record_id'))
            ->orWhere('uuid', $this->argument('record_id'))
            ->first();

        if (! $record) {
            $this->error('Compiled record not found.');

            return self::FAILURE;
        }

        $datasheet = $record->datasheet;
        $submittedBy = $record->metadata['submitted_by'] ?? $record->metadata['uploaded_by'] ?? $datasheet?->metadata['uploaded_by'] ?? null;
        $notifications = Notification::query()
            ->where(fn ($query) => $query
                ->where('metadata->compiled_device_record_id', $record->id)
                ->orWhereHas('activity', fn ($activity) => $activity->where('compiled_device_record_id', $record->id)))
            ->count();

        $this->table(['Field', 'Value'], [
            ['datasheet id', $datasheet?->id ?? 'none'],
            ['compiled record id', $record->id],
            ['compiled record uuid', $record->uuid],
            ['source_type', $record->source_type],
            ['manufacturer', $record->manufacturer ?? 'none'],
            ['owner manufacturer company', $datasheet?->metadata['manufacturer_company_id'] ?? 'none'],
            ['status', $record->status],
            ['review_status', $record->review_status ?? $record->metadata['review_status'] ?? 'none'],
            ['submitted_by', $submittedBy ?? 'none'],
            ['submitted_at', $record->metadata['submitted_at'] ?? 'none'],
            ['detected_manufacturer', $record->metadata['detected_manufacturer'] ?? 'none'],
            ['manufacturer_mismatch_detected', ($record->metadata['manufacturer_mismatch_detected'] ?? false) ? 'yes' : 'no'],
            ['notifications created', $notifications],
            ['expected librarian visibility', $this->expectedLibrarianVisibility($record)],
        ]);

        if ($record->reviewComments->isNotEmpty()) {
            $this->line('');
            $this->info('Review comments');
            $this->table(['Action', 'Actor', 'Comment', 'Created'], $record->reviewComments->map(fn ($comment): array => [
                $comment->action,
                $comment->actor?->email ?? 'system',
                $comment->comment ?? '',
                $comment->created_at?->toDateTimeString(),
            ])->all());
        }

        return self::SUCCESS;
    }

    private function expectedLibrarianVisibility(CompiledDeviceRecord $record): string
    {
        if (
            in_array($record->source_type, ['central_curated', 'partner_submitted'], true)
            && ($record->status === 'submitted_for_approval' || ($record->review_status ?? null) === 'submitted')
        ) {
            return 'visible in /admin/library/pending-approval';
        }

        if (
            in_array($record->source_type, ['central_curated', 'partner_submitted'], true)
            && in_array($record->status, ['librarian_review', 'changes_requested'], true)
        ) {
            return 'visible in /admin/library/review';
        }

        if ($record->source_type === 'central_curated' && $record->status === 'published') {
            return 'visible in public search';
        }

        return 'not expected in Librarian queues';
    }
}
