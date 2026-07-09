<?php

namespace App\LineWatt\Publishing;

final class PublishingEvent
{
    public const DATASHEET_UPLOADED = 'DatasheetUploaded';
    public const ENGINEERING_RECORD_COMPILED = 'EngineeringRecordCompiled';
    public const ENGINEERING_RECORD_COMPILE_FAILED = 'EngineeringRecordCompileFailed';
    public const ENGINEERING_RECORD_SUBMITTED_FOR_APPROVAL = 'EngineeringRecordSubmittedForApproval';
    public const ENGINEERING_RECORD_APPROVED = 'EngineeringRecordApproved';
    public const ENGINEERING_RECORD_PUBLISHED = 'EngineeringRecordPublished';
    public const ENGINEERING_RECORD_REJECTED = 'EngineeringRecordRejected';
    public const ENGINEERING_RECORD_CHANGES_REQUESTED = 'EngineeringRecordChangesRequested';
    public const MANUFACTURER_SUBMISSION_CREATED = 'ManufacturerSubmissionCreated';
    public const MALWARE_BLOCKED_UPLOAD = 'MalwareBlockedUpload';
}
