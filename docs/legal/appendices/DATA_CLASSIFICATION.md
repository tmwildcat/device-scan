# Data Classification

**Document:** DATA CLASSIFICATION STANDARD  
**Version:** 1.0 Draft  
**Status:** Draft for Legal Review  
**Last Updated:** [LAST UPDATED DATE]

> **Internal operational draft; control mappings require security approval.**

## 1. Purpose and scope

This standard assigns handling levels to Platform data. The highest applicable classification controls.

## 2. Classes

| Class | Meaning | Examples | Minimum handling intent |
|---|---|---|---|
| Public | deliberately approved for public release | Published Engineering Records; approved public metadata; public policies | integrity, provenance, publication approval |
| Internal | non-public, limited business sensitivity | product documentation; aggregate operations metrics | workforce/approved-provider access |
| Confidential | disclosure could harm a person, User, or business | unpublished Manufacturer Content; Account data; payment metadata; support tickets; ordinary audit logs | need-to-know, protected transfer/storage, logged administration |
| Restricted | credentials, tenant-private or high-impact data | Private Datasets; source uploads in private tenant; secrets/tokens; security evidence; sensitive legal records | least privilege, strong authentication, scoped service access, heightened logging and disposal |

## 3. Specific mappings

Private Dataset and dependent Source Document data are Restricted even when internally indexed. Manufacturer Content is Confidential until deliberately Published. Credential material is Restricted; raw secrets must not appear in audit summaries. Public facts may retain Confidential or Restricted provenance evidence.

## 4. Governance and related documents

Owners must label systems and exports, approve downgrades, and respond to incidents based on impact. See the [Security Policy](../governance/SECURITY_POLICY.md), [Document Retention Policy](../privacy/DOCUMENT_RETENTION_POLICY.md), and [Open Data and Export Licensing Policy](../governance/OPEN_DATA_AND_EXPORT_LICENSING_POLICY.md).

## 5. Revision history

| Version | Date | Change |
|---|---|---|
| 1.0 Draft | [LAST UPDATED DATE] | Initial classification model. |
