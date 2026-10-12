# API Design

## Authentication

- **Laravel Sanctum** for API token authentication
- Tokens scoped per user with abilities

## Endpoint Convention

```
/api/v1/{resource}
```

### Current API Routes
Check `routes/api.php` for the latest API endpoint definitions.

## Response Format

### Success Response
```json
{
    "data": { ... },
    "message": "Success"
}
```

### Success with Pagination
```json
{
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    },
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    }
}
```

### Error Response
```json
{
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

## API Resources

Use Laravel API Resources for response formatting:

```php
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'position' => new PositionResource($this->whenLoaded('position')),
            'is_active' => $this->is_active,
            'join_date' => $this->join_date->format('Y-m-d'),
        ];
    }
}
```

## HTTP Status Codes

| Code | Usage |
|------|-------|
| 200 | Success |
| 201 | Created |
| 204 | No Content (delete) |
| 401 | Unauthenticated |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Rate Limited |

## Best Practices

1. **API versioning** — Use `/api/v1/` prefix
2. **Rate limiting** — Apply to authentication endpoints
3. **Tenant isolation** — Scope all queries by company_id
4. **Validation** — Use Form Request classes
5. **Eager loading** — Prevent N+1 in API responses
6. **Pagination** — Always paginate list endpoints
