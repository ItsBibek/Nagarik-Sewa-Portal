# Nagarik Sewa Portal API v1

This document describes the current JSON API for the one-service, selected-ward demo. It is a hand-maintained reference; no API-documentation package is required.

## Base URL and response envelope

For the local Laravel Herd site, use:

```text
http://nagarik-sewa-portal.test/api/v1
```

If using `php artisan serve`, use `http://127.0.0.1:8000/api/v1`.

Responses use this envelope:

```json
{
  "data": {},
  "message": "Request completed."
}
```

Paginated application lists also include Laravel `links` and `meta` fields. Dates are ISO 8601 strings. Application resources intentionally omit document file paths, internal officer remarks, and internal officer IDs. Public tracking does not return document metadata.

## Authentication

Create a Sanctum token with `POST /tokens`:

```http
POST /api/v1/tokens
Content-Type: application/json
Accept: application/json
```

```json
{
  "email": "citizen@example.test",
  "password": "your-password",
  "device_name": "postman"
}
```

Successful response (`200`):

```json
{
  "data": {
    "token": "1|replace-with-issued-token",
    "token_type": "Bearer"
  },
  "message": "API token created."
}
```

For protected requests send:

```http
Authorization: Bearer <token>
Accept: application/json
```

Keep bearer tokens secret. Seeded local demo identities are listed in the README; citizens can also register through the web application.

## Endpoint summary

| Method | Endpoint | Authentication / authorization | Notes |
| --- | --- | --- | --- |
| `GET` | `/services` | Public | Active services and example fees/documents |
| `POST` | `/tokens` | Public; login throttle | Issues a token after credential validation |
| `GET` | `/track/{tracking_id}` | Public; 20/minute/IP | Safe public status and history |
| `GET` | `/user` | Sanctum | Safe account profile |
| `GET` | `/applications` | Sanctum | Citizen-owned, officer-department, or admin listing; optional `?status=` |
| `GET` | `/applications/{application}` | Sanctum + policy | Only authorized records |
| `POST` | `/applications` | Citizen token | Multipart submission |
| `POST` | `/applications/{application}/corrections` | Owning citizen, correction requested | Multipart replacement documents |
| `PATCH` | `/applications/{application}/status` | Officer/admin + policy | A valid state transition |
| `DELETE` | `/tokens/current` | Sanctum | Revoke only the current token |

All paths are relative to `/api/v1`.

## Read service requirements

```http
GET /api/v1/services
Accept: application/json
```

Example:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Residential Recommendation Letter",
      "department": "Kathmandu Metropolitan City — Selected Ward Office (demo)",
      "example_fee_npr": 500,
      "sample_processing_days": 5,
      "example_required_documents": [
        "citizenship_certificate",
        "proof_of_residence"
      ]
    }
  ],
  "message": "Available services retrieved."
}
```

The ID, fee, document requirements, and processing estimate come from active database configuration. Seeded fee/documents/timing are demo examples, not verified official ward requirements or charges.

## Submit an application

Submission requires a citizen token and `multipart/form-data`:

```http
POST /api/v1/applications
Authorization: Bearer <citizen-token>
Accept: application/json
Content-Type: multipart/form-data
```

In Postman choose **Body → form-data**:

| Key | Type | Value |
| --- | --- | --- |
| `service_type_id` | Text | The active ID returned by `GET /services` |
| `documents[0]` | File | Citizenship certificate (PDF, JPG, or PNG; up to 5 MB) |
| `documents[1]` | File | Proof of residence (PDF, JPG, or PNG; up to 5 MB) |

Document field indexes correspond to the service's `example_required_documents` order. Requirements are validated server-side.

Successful response (`201 Created`):

```json
{
  "data": {
    "id": 12,
    "tracking_id": "NSP-AB12CD34EF56",
    "status": "submitted",
    "submitted_at": "2026-10-17T10:00:00+00:00",
    "service": {
      "id": 1,
      "name": "Residential Recommendation Letter",
      "department": "Kathmandu Metropolitan City — Selected Ward Office (demo)",
      "fee_npr": 500
    },
    "documents": [
      {
        "type": "citizenship_certificate",
        "uploaded_at": "2026-10-17T10:00:00+00:00"
      },
      {
        "type": "proof_of_residence",
        "uploaded_at": "2026-10-17T10:00:00+00:00"
      }
    ],
    "status_history": [
      {
        "status": "submitted",
        "changed_at": "2026-10-17T10:00:00+00:00"
      }
    ]
  },
  "message": "Application submitted."
}
```

The API returns document type and upload time, never the private stored path.

## List and view applications

```http
GET /api/v1/applications?status=submitted
Authorization: Bearer <token>
Accept: application/json
```

Supported statuses are `submitted`, `under_review`, `correction_required`, `approved`, and `rejected`. Listing visibility is role-scoped: citizens see their own applications, officers see active-service applications in their department, and admins can view all applications.

View one policy-authorized record:

```http
GET /api/v1/applications/12
Authorization: Bearer <token>
Accept: application/json
```

Both endpoints return application resources using the shape shown in the successful submission response; list responses are paginated.

## Resubmit correction documents

Only the owning citizen can resubmit an application while its status is `correction_required`:

```http
POST /api/v1/applications/12/corrections
Authorization: Bearer <citizen-token>
Accept: application/json
Content-Type: multipart/form-data
```

Use **Body → form-data** with `documents[0]` and `documents[1]` file fields, following the active service's document order and allowed formats. On success, the response message is `Replacement documents submitted.` and the application moves to `under_review`, with a history entry.

## Officer status update

An officer must belong to the application's service department and satisfy the application policy. Admins may also update application status. Send JSON:

```http
PATCH /api/v1/applications/12/status
Authorization: Bearer <officer-or-admin-token>
Accept: application/json
Content-Type: application/json
```

Example request:

```json
{
  "status": "correction_required",
  "remark": "Please upload a clearer copy of the residence document."
}
```

Valid transitions are enforced by the application workflow. A newly submitted application must first move to `under_review`; correction-required and rejected decisions require a remark. Successful responses contain the updated safe application resource and message `Application status updated.`.

## Public tracking

Tracking is unauthenticated, rate-limited to 20 requests per minute per IP, and returns no documents or officer remarks:

```http
GET /api/v1/track/NSP-AB12CD34EF56
Accept: application/json
```

Example:

```json
{
  "data": {
    "tracking_id": "NSP-AB12CD34EF56",
    "status": "under_review",
    "service": "Residential Recommendation Letter",
    "ward_office": "Kathmandu Metropolitan City — Selected Ward Office (demo)",
    "submitted_at": "2026-10-17T10:00:00+00:00",
    "status_history": [
      {
        "status": "submitted",
        "changed_at": "2026-10-17T10:00:00+00:00"
      },
      {
        "status": "under_review",
        "changed_at": "2026-10-17T11:00:00+00:00"
      }
    ]
  },
  "message": "Tracking information retrieved."
}
```

## Profile and token revocation

Get the current token owner's safe profile:

```http
GET /api/v1/user
Authorization: Bearer <token>
Accept: application/json
```

Revoke the current token (other issued tokens are not affected):

```http
DELETE /api/v1/tokens/current
Authorization: Bearer <token>
Accept: application/json
```

Successful revocation:

```json
{
  "data": null,
  "message": "API token revoked."
}
```

## Errors

Validation failure (`422 Unprocessable Entity`):

```json
{
  "data": {
    "errors": {
      "documents.0": [
        "The citizenship certificate field is required."
      ]
    }
  },
  "message": "The given data was invalid."
}
```

Other standard errors use the same envelope:

| HTTP | Message |
| --- | --- |
| `401` | `Unauthenticated.` |
| `403` | `You are not authorized to perform this action.` |
| `404` | `The requested resource was not found.` |
| `429` | `Too many requests. Please try again later.` |

## Try it with Postman or Insomnia

1. Call `GET http://nagarik-sewa-portal.test/api/v1/services` to obtain the active service ID and document order.
2. Call `POST http://nagarik-sewa-portal.test/api/v1/tokens` with the JSON token request above.
3. Copy `data.token` and use `Authorization: Bearer <token>` for private endpoints.
4. Submit an application as multipart form-data, attaching the two required files using their indexed field names.
5. Copy the returned tracking ID and call `GET /api/v1/track/{tracking_id}` without an authorization header.
6. Authenticate using the seeded officer account to exercise status updates; try a correction/rejection without a remark to see the 422 response.
