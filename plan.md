# Plan: AI-Powered CRUD Chatbot — Laravel 12

## TL;DR
Build a Laravel 12 API-only backend: **AI → Decision → Backend Execution System**. User sends natural language → Laravel forwards to hellozed AI API with strict system prompt → AI returns structured JSON → Laravel re-validates (never trust AI), dispatches CRUD action via strategy pattern → stores in MySQL → returns consistent response. Clean architecture, extensible to new entities.

---

## Folder Structure

```
ai-crud-chatbot/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   └── ChatController.php          # Single entry point for chat messages
│   │   ├── Requests/
│   │   │   └── ChatMessageRequest.php       # Validates incoming user message
│   │   └── Resources/
│   │       ├── ChatResponseResource.php     # Wraps final API response
│   │       └── CustomerResource.php         # Formats customer data
│   │
│   ├── Services/
│   │   ├── AI/
│   │   │   ├── AIService.php               # Calls external AI API via Http client
│   │   │   ├── AIResponseParser.php        # Safely parses & validates AI JSON
│   │   │   └── SystemPromptBuilder.php     # Builds strict system prompt for AI
│   │   │
│   │   ├── Actions/
│   │   │   ├── ActionDispatcher.php        # Maps action string → handler (strategy router)
│   │   │   ├── Contracts/
│   │   │   │   └── ActionHandlerInterface.php  # execute(array $data): ActionResult
│   │   │   └── Handlers/
│   │   │       ├── CreateCustomerHandler.php
│   │   │       ├── UpdateCustomerHandler.php
│   │   │       ├── DeleteCustomerHandler.php
│   │   │       └── ReadCustomerHandler.php
│   │   │
│   │   └── Customer/
│   │       └── CustomerService.php          # Pure CRUD logic for customers
│   │
│   ├── DTOs/
│   │   ├── AIResponseDTO.php               # Typed object for parsed AI response
│   │   └── ActionResultDTO.php             # Typed result from action execution
│   │
│   ├── Enums/
│   │   └── ActionType.php                  # Enum: create_customer, update_customer, etc.
│   │
│   ├── Models/
│   │   ├── Customer.php
│   │   └── ChatLog.php                     # Audit log of every chat interaction
│   │
│   ├── Exceptions/
│   │   ├── AIResponseException.php         # AI returned invalid/unparseable response
│   │   └── InvalidActionException.php      # Unknown action type
│   │
│   └── Providers/
│       └── ActionServiceProvider.php       # Registers action handlers in container
│
├── config/
│   └── ai.php                              # AI API URL, timeout, retry, system prompt
│
├── database/
│   └── migrations/
│       ├── xxxx_create_customers_table.php
│       └── xxxx_create_chat_logs_table.php
│
├── routes/
│   └── api.php                             # POST /api/chat
│
└── tests/
    ├── Feature/
    │   └── ChatEndpointTest.php
    └── Unit/
        ├── AIResponseParserTest.php
        ├── ActionDispatcherTest.php
        └── CustomerServiceTest.php
```

---

## Steps

### Phase 1: Project Scaffold & Config
1. Initialize Laravel 12 project (`laravel new ai-crud-chatbot` or `composer create-project`)
2. Configure MySQL connection in `.env`
3. Create `config/ai.php` with keys: `api_url`, `api_timeout`, `max_retries`, `system_prompt`
4. Remove unnecessary frontend scaffolding (Blade views, Vite config) — API-only

### Phase 2: Database Layer
5. Create `customers` migration — columns: `id`, `name`, `email` (unique), `phone`, `address`, `timestamps`, `soft_deletes`
6. Create `chat_logs` migration — columns: `id`, `user_message`, `ai_raw_response`, `parsed_action`, `status` (enum: success/failed/invalid), `error_message` (nullable), `timestamps`
7. Create `Customer` model with fillable, casts, soft deletes
8. Create `ChatLog` model with fillable, casts

### Phase 3: AI Integration Layer
9. Create `config/ai.php` — externalize API URL, timeout, system prompt template
10. Create `SystemPromptBuilder` — builds the strict system prompt that instructs the AI to return only valid JSON with `action`, `data`, `valid` fields. Defines allowed actions and required fields per action
11. Create `AIService` — uses Laravel `Http::post()` to call `https://ai.hellozed.com/api/zedbot/chat`, sends user message + system prompt, handles timeouts/retries, returns raw response. *Depends on step 9-10*
12. Create `AIResponseDTO` — typed value object with properties: `action`, `data` (array), `valid` (bool), `error` (nullable string)
13. Create `AIResponseParser` — json_decode with error handling, maps to `AIResponseDTO`, throws `AIResponseException` on malformed response. *Depends on step 12*

### Phase 4: Action Dispatch Layer (Strategy Pattern)
14. Create `ActionType` enum — cases: `CREATE_CUSTOMER`, `UPDATE_CUSTOMER`, `DELETE_CUSTOMER`, `READ_CUSTOMER` with `fromString()` method
15. Create `ActionHandlerInterface` — contract: `execute(array $validatedData): ActionResultDTO`
16. Create `ActionResultDTO` — properties: `success` (bool), `message` (string), `resourceUrl` (nullable string), `data` (nullable array)
17. Create `CreateCustomerHandler` — validates data via Laravel Validator (name required, email valid+unique, phone required), calls `CustomerService::create()`, returns `ActionResultDTO` with resource URL `/api/customers/{id}`. *Depends on steps 15-16, and Phase 5*
18. Create `UpdateCustomerHandler`, `DeleteCustomerHandler`, `ReadCustomerHandler` similarly. *Parallel with step 17*
19. Create `ActionDispatcher` — accepts `AIResponseDTO`, resolves correct handler from container by action string, calls `execute()`. *Depends on steps 14-18*
20. Create `ActionServiceProvider` — binds each action string to its handler class in the service container. *Depends on step 19*

### Phase 5: Customer Service (CRUD)
21. Create `CustomerService` — methods: `create(array)`, `update(int, array)`, `delete(int)`, `find(int)`, `findByEmail(string)`. Pure Eloquent operations. *Parallel with Phase 4*

### Phase 6: API Layer
22. Create `ChatMessageRequest` — validates: `message` required, string, max:1000
23. Create `ChatResponseResource` — formats: `success`, `message`, `data`, `resource_url`, `errors`
24. Create `CustomerResource` — formats customer model fields
25. Create `ChatController@handle` — orchestrates the flow: receive message → call AIService → parse response → if invalid return error → validate data → dispatch action → log to ChatLog → return ChatResponseResource. *Depends on all previous phases*
26. Register route in `api.php`: `POST /api/chat` → `ChatController@handle`
27. Optionally add `GET /api/customers/{id}` for resource URL resolution

### Phase 7: Error Handling & Logging
28. Create `AIResponseException` and `InvalidActionException`
29. Register exception rendering in `bootstrap/app.php` — return consistent JSON error responses
30. Ensure every chat interaction is logged to `chat_logs` (success or failure)

### Phase 8: Testing & Verification
31. Unit test `AIResponseParser` — valid JSON, invalid JSON, missing fields, valid=false
32. Unit test `ActionDispatcher` — correct handler resolution, unknown action
33. Unit test `CustomerService` — CRUD operations
34. Feature test `POST /api/chat` — mock AIService, test full flow end-to-end
35. Feature test error scenarios — AI timeout, invalid JSON, validation failure

---

## Request/Response Flow

```
User → POST /api/chat { "message": "Customer Entry: Naeem..." }
  → ChatController@handle
    → AIService::chat(message)        // calls external AI API
    → AIResponseParser::parse(raw)    // returns AIResponseDTO
    → if (!dto.valid) → return error
    → ActionDispatcher::dispatch(dto) // resolves handler by action
      → CreateCustomerHandler::execute(dto.data)
        → Laravel Validator (server-side re-validation)
        → CustomerService::create(validatedData)
        → return ActionResultDTO
    → ChatLog::create(...)            // audit log
    → return ChatResponseResource
User ← { "success": true, "message": "Customer created", "resource_url": "/api/customers/1", "data": {...} }
```

---

## Relevant Files (to create)

- `app/Http/Controllers/Api/ChatController.php` — main orchestrator, ~40 lines
- `app/Services/AI/AIService.php` — Http::post to external API, retry logic
- `app/Services/AI/AIResponseParser.php` — safe JSON parsing to DTO
- `app/Services/AI/SystemPromptBuilder.php` — strict prompt instructing AI output format
- `app/Services/Actions/ActionDispatcher.php` — strategy pattern router
- `app/Services/Actions/Contracts/ActionHandlerInterface.php` — handler contract
- `app/Services/Actions/Handlers/CreateCustomerHandler.php` — create + validate
- `app/Services/Customer/CustomerService.php` — Eloquent CRUD
- `app/DTOs/AIResponseDTO.php` — value object
- `app/DTOs/ActionResultDTO.php` — value object
- `app/Enums/ActionType.php` — backed enum
- `app/Models/Customer.php` — Eloquent model
- `app/Models/ChatLog.php` — audit log model
- `config/ai.php` — AI config externalization
- `routes/api.php` — POST /api/chat route
- `database/migrations/*` — customers + chat_logs tables

## Verification

1. Run `php artisan migrate` — confirm tables created
2. Run `php artisan test --filter=AIResponseParserTest` — parser handles all edge cases
3. Run `php artisan test --filter=ChatEndpointTest` — full flow with mocked AI API
4. Manual test via Postman/curl: `POST /api/chat` with sample messages:
   - Valid: `"Customer Entry: Naeem Sarker, naeem@gmail.com, 01678789233, Uttara, Dhaka"`
   - Invalid email: `"Customer Entry: Naeem, bad-email, 123, Dhaka"`
   - Gibberish: `"asdfasdf"`
5. Verify `chat_logs` table records every interaction
6. Verify duplicate email returns validation error (not a 500)

## Decisions

- **Strategy pattern for action dispatch** — each action is a separate handler class, registered in the container. Adding a new entity (e.g., Product) means adding new handlers + registering them, zero changes to existing code (Open/Closed Principle)
- **Double validation** — AI validates first (returns `valid: false`), then Laravel Validator re-validates in the handler. Never trust external AI output
- **ChatLog as audit trail** — every interaction logged regardless of outcome for debugging and analytics
- **Soft deletes on Customer** — recoverable deletions
- **Config externalization** — AI API URL, timeout, system prompt all in `config/ai.php`, swappable per environment
- **No authentication in Phase 1** — can be added later via Sanctum

## Further Considerations

1. **Authentication**: Add Laravel Sanctum token auth to protect `POST /api/chat`. Recommend adding in a follow-up phase to keep initial scope focused
2. **Rate limiting**: The external AI API may have rate limits. Consider adding Laravel's built-in rate limiter (`ThrottleRequests`) on the chat endpoint — recommend 30 requests/minute per user
3. **Extensibility to other entities**: The current plan supports Customer only. The strategy pattern makes it trivial to add Product, Order, etc. — just create new handlers and register them. Should we plan for a second entity now or defer?
