# Public Legal Portal

`GET /legal` lists active public documents only when a Published effective version exists. `GET /legal/{slug}/{version?}` renders frozen sanitised HTML and approved public history.

Internal, restricted, confidential, Draft, Approved-but-unpublished, Withdrawn, and future-effective content is not displayed. The templates provide printable, semantic HTML. PDF links can be added after public artifact-download authorisation and presentation review.
