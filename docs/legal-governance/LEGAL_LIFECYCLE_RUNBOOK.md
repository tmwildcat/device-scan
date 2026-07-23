# Legal Document Lifecycle Runbook

## Roles

| Action | Legal Publisher | Legal Counsel | Super Administrator |
|---|---:|---:|---:|
| Author and edit Draft | Yes | Yes | Yes |
| Submit and view review | Yes | Yes | Yes |
| Review, return, reject, approve | No | Yes | Yes |
| Schedule, publish, cancel, withdraw, archive | No | Yes | Yes |
| Manage workflows and acceptance administration | No | Yes | Yes |

Super Administrator access uses the existing platform override. Legal Counsel may approve their own work; maker-checker separation is not required.

## Draft to publication

1. Save Draft content and metadata.
2. Resolve requirements needed for review and submit. Submission records actor and timestamp and places the checksum-bound version in the Review Queue.
3. Legal Counsel records review decisions. Changes Requested requires a comment and returns content to an editable path.
4. Approve only after required reviews match the current checksum. Approval freezes content and governed metadata and stores actor, timestamp, checksum, and metadata snapshot.
5. Review all ten publication-readiness dimensions.
6. Publish now or schedule a future publication. Approval and publication remain separate audited transitions.

If an approved version needs correction, use Return to Draft with a reason. This invalidates current approval evidence. Edit, resubmit, review, and approve again.

## Scheduling and recovery

The scheduler runs `legal:publish-scheduled` each minute. At execution it locks and revalidates the version, retains the scheduling actor, and publishes transactionally. A failed execution remains Scheduled and produces `legal_scheduled_publication_failed` audit evidence. Correct the readiness problem or cancel the schedule with a reason, returning the version to Approved.

## Withdrawal and archival

Withdrawal requires a reason and immediately excludes a Published version from public resolution and new acceptance selection. It does not restore an older version automatically. Archival is allowed for obsolete Draft, Changes Requested, Withdrawn, or Superseded versions, never the current Published version.

## Footer and acceptance enforcement

The public footer resolves only active public documents with Published, effective versions. Draft, In Review, Approved, future Scheduled, Withdrawn, and Archived versions are excluded. Acceptance Enforcement likewise selects only Published and effective versions. A material-change publication creates deduplicated re-acceptance obligations only for subjects with qualifying prior acceptance evidence in affected active workflows.

## Validation

Run:

```bash
php artisan legal:validate-documents
php artisan legal:validate-documents --production
php artisan legal:validate-enforcement --allow-staged
php artisan legal:verify-integrity --actor=operator
```

Production validation fails for required footer publications that are absent, non-public, inactive, unpublished, or not yet effective.
