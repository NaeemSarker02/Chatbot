# AI CRUD Chatbot — Complete Development Guide

> **Author:** Senior Laravel Architect
> **For:** Junior/Fresher developers who want to understand and own this project
> **Goal:** Step-by-step teaching guide — পুরো project কিভাবে কাজ করে, কোন file কেন বানানো হয়েছে, কার সাথে কার connection, এবং কিভাবে develop করতে হয়

---

## Table of Contents

1. [Project এর মূল ধারণা (Core Concept)](#1-project-এর-মূল-ধারণা)
2. [Tech Stack](#2-tech-stack)
3. [পুরো System কিভাবে কাজ করে (Full Workflow)](#3-পুরো-system-কিভাবে-কাজ-করে)
4. [Folder Structure — কোথায় কী আছে](#4-folder-structure--কোথায়-কী-আছে)
5. [প্রতিটি File এর বিস্তারিত ব্যাখ্যা (File-by-File Breakdown)](#5-প্রতিটি-file-এর-বিস্তারিত-ব্যাখ্যা)
6. [File Connection Map — কে কাকে call করে](#6-file-connection-map--কে-কাকে-call-করে)
7. [Step-by-Step Development Guide — শূন্য থেকে শুরু](#7-step-by-step-development-guide)
8. [Database Design](#8-database-design)
9. [API Endpoints](#9-api-endpoints)
10. [AI Integration — কিভাবে AI এর সাথে কথা বলে](#10-ai-integration)
11. [Error Handling Strategy](#11-error-handling-strategy)
12. [Design Patterns Used](#12-design-patterns-used)
13. [Setup Instructions](#13-setup-instructions)
14. [Postman Testing Guide](#14-postman-testing-guide)
15. [নতুন Entity যোগ করার নিয়ম (Extensibility)](#15-নতুন-entity-যোগ-করার-নিয়ম)
16. [Golden Rules](#16-golden-rules)

---

## 1. Project এর মূল ধারণা

### এটা কী?

এটা একটা **AI → Decision → Backend Execution System**।

এটা সাধারণ chatbot না। এটা একটা intelligent system যেটা:
- User এর কথা (natural language) বুঝতে পারে
- সিদ্ধান্ত নেয় কী করতে হবে (Create? Update? Delete?)
- Database এ সেই কাজটা করে
- Result ফেরত দেয়

### সহজ উদাহরণ:

```
User বলে: "ভাই একটা কাস্টমার সেভ করো - Naeem, naeem@gmail.com, 01678789233, Dhaka"

System বুঝে নেয়: ও, এটা নতুন customer create করতে হবে!
System করে: Database এ customer save করে
System বলে: "Customer created! URL: /api/customers/1"
```

### মূল কথা:

User কে কোনো form fill-up করতে হয় না। User শুধু কথা বলে (text), আর system নিজে বুঝে নিয়ে কাজ করে।

---

## 2. Tech Stack

| Layer           | Technology                                    | কেন ব্যবহার করা হয়েছে         |
|----------------|-----------------------------------------------|-------------------------------|
| Framework      | Laravel 12                                     | PHP এর সবচেয়ে popular framework, clean architecture support |
| Language       | PHP 8.2+                                       | Modern PHP features (enums, readonly, named args) |
| Database       | MySQL                                          | Relational data storage       |
| AI API         | https://ai.hellozed.com/api/zedbot/chat       | Natural language → structured JSON conversion |
| Design Pattern | Strategy Pattern                               | Dynamic action routing        |
| Testing        | PHPUnit                                        | Unit + Feature tests          |

---

## 3. পুরো System কিভাবে কাজ করে

### Simple Diagram:

```
User (Postman/Frontend)
  │
  │  POST /api/chat  { "message": "কাস্টমার সেভ করো..." }
  ▼
┌─────────────────────────────────────────────────────┐
│  ChatController (traffic police — সব manage করে)     │
│                                                      │
│  Step 1: Message validate করে (খালি? বেশি লম্বা?)    │
│          ↓                                           │
│  Step 2: AIService কে ডাকে — "এই message টা AI কে   │
│          পাঠাও, structured JSON ফেরত আনো"            │
│          ↓                                           │
│  Step 3: AIResponseParser — AI যা পাঠিয়েছে সেটা     │
│          safely parse করে AIResponseDTO তে রাখে      │
│          ↓                                           │
│  Step 4: AI বলেছে valid? না হলে error return করো     │
│          ↓                                           │
│  Step 5: ActionDispatcher কে ডাকে — "এই action টা    │
│          সঠিক handler এ পাঠাও"                       │
│          ↓                                           │
│  Step 6: Handler (যেমন CreateCustomerHandler)         │
│          → আবার validate করে (AI কে trust করো না!)   │
│          → CustomerService দিয়ে database এ save করে  │
│          → ActionResultDTO ফেরত দেয়                  │
│          ↓                                           │
│  Step 7: ChatLog এ log রাখে (success হোক বা fail)    │
│          ↓                                           │
│  Step 8: ChatResponseResource দিয়ে clean JSON        │
│          response পাঠায়                              │
└─────────────────────────────────────────────────────┘
  │
  ▼
User ← { "success": true, "message": "Customer created", "url": "/api/customers/1" }
```

### Data Flow (ডাটা কোন পথে যায়):

```
User Message (string)
  → AIService → AI API → raw AI response (string)
    → AIResponseParser → AIResponseDTO (typed object)
      → ActionDispatcher → correct Handler
        → Laravel Validator → validated data
          → CustomerService → Eloquent → MySQL
            → ActionResultDTO
              → ChatResponseResource → JSON Response
```

---

## 4. Folder Structure — কোথায় কী আছে

```
ai-crud-chatbot/
│
├── app/                          ← মূল application code এখানে
│   │
│   ├── Http/                     ← HTTP layer — request/response handle করে
│   │   ├── Controllers/Api/
│   │   │   └── ChatController.php         ← 🎯 MAIN FILE — পুরো flow manage করে
│   │   ├── Requests/
│   │   │   └── ChatMessageRequest.php     ← User input validate করে
│   │   └── Resources/
│   │       ├── ChatResponseResource.php   ← API response format করে
│   │       └── CustomerResource.php       ← Customer data format করে
│   │
│   ├── Services/                 ← Business logic — সব বুদ্ধি এখানে
│   │   ├── AI/                   ← AI সংক্রান্ত সব কাজ
│   │   │   ├── AIService.php              ← AI API call করে
│   │   │   ├── AIResponseParser.php       ← AI response parse করে
│   │   │   └── SystemPromptBuilder.php    ← AI কে instruction দেয়
│   │   │
│   │   ├── Actions/              ← কোন action কে handle করবে সেটা ঠিক করে
│   │   │   ├── ActionDispatcher.php       ← Action → Handler routing
│   │   │   ├── Contracts/
│   │   │   │   └── ActionHandlerInterface.php  ← Handler দের blueprint
│   │   │   └── Handlers/         ← প্রতিটা action এর জন্য আলাদা handler
│   │   │       ├── CreateCustomerHandler.php   ← Customer create
│   │   │       ├── UpdateCustomerHandler.php   ← Customer update
│   │   │       ├── DeleteCustomerHandler.php   ← Customer delete
│   │   │       └── ReadCustomerHandler.php     ← Customer read
│   │   │
│   │   └── Customer/
│   │       └── CustomerService.php        ← Database CRUD operations
│   │
│   ├── DTOs/                     ← Data Transfer Objects — typed data carriers
│   │   ├── AIResponseDTO.php             ← AI response কে typed object এ রাখে
│   │   └── ActionResultDTO.php           ← Action result কে typed object এ রাখে
│   │
│   ├── Enums/
│   │   └── ActionType.php                ← Allowed action গুলোর list
│   │
│   ├── Models/                   ← Database tables এর PHP representation
│   │   ├── Customer.php                  ← customers table
│   │   └── ChatLog.php                   ← chat_logs table (audit trail)
│   │
│   ├── Exceptions/               ← Custom error classes
│   │   ├── AIResponseException.php       ← AI error হলে
│   │   └── InvalidActionException.php    ← Unknown action হলে
│   │
│   └── Providers/
│       └── ActionServiceProvider.php     ← Action handlers register করে
│
├── config/
│   └── ai.php                    ← AI API settings (URL, key, timeout)
│
├── database/migrations/          ← Database table definitions
│   ├── ..._create_customers_table.php
│   └── ..._create_chat_logs_table.php
│
├── routes/
│   └── api.php                   ← API routes define করা আছে
│
├── bootstrap/
│   ├── app.php                   ← App configuration (routes, middleware, exceptions)
│   └── providers.php             ← Service providers register
│
├── tests/
│   ├── Unit/                     ← Unit tests (individual components)
│   │   ├── AIResponseParserTest.php
│   │   └── ActionDispatcherTest.php
│   └── Feature/                  ← Feature tests (full flow)
│       ├── ChatEndpointTest.php
│       └── CustomerServiceTest.php
│
├── .env                          ← Environment variables (DB, API keys)
└── Guideline.md                  ← এই ফাইল — project documentation
```

---

## 5. প্রতিটি File এর বিস্তারিত ব্যাখ্যা

### Layer 1: Configuration (শুরুতেই setup)

#### `config/ai.php` — AI Settings

```
কাজ: AI API এর সব settings এক জায়গায় রাখে
কেন: Hardcode করলে পরে পরিবর্তন কঠিন। Config file এ রাখলে .env থেকে সহজে পরিবর্তন করা যায়
```

এখানে আছে:
- `api_url` → AI API এর URL
- `api_key` → API authentication token
- `api_timeout` → কতক্ষণ wait করবে (30 seconds)
- `max_retries` → fail হলে কতবার retry করবে (2 বার)
- `system_prompt` → custom prompt (default খালি — code এ built-in আছে)

#### `.env` — Environment Variables

```
কাজ: Server-specific settings রাখে (DB password, API key ইত্যাদি)
কেন: প্রতিটা environment (local/staging/production) এ আলাদা values লাগে
```

মূল settings:
```env
DB_DATABASE=ai_crud_chatbot        # Database নাম
DB_USERNAME=root                   # Database user
DB_PASSWORD=                       # Database password
AI_API_URL=https://ai.hellozed.com/api/zedbot/chat   # AI API URL
AI_API_KEY=zed_xxxx                # AI API token
AI_API_TIMEOUT=30                  # Timeout seconds
AI_MAX_RETRIES=2                   # Retry count
```

#### `bootstrap/app.php` — App Bootstrap

```
কাজ: Laravel app কে configure করে — routes, middleware, exceptions register করে
মূল পরিবর্তন: api.php route register করা হয়েছে
```

#### `bootstrap/providers.php` — Service Providers

```
কাজ: কোন কোন Service Provider load হবে সেটা বলে দেয়
মূল পরিবর্তন: ActionServiceProvider যোগ করা হয়েছে
```

#### `routes/api.php` — API Routes

```
কাজ: কোন URL এ hit করলে কোন controller method চলবে সেটা define করে
```

দুটো route আছে:
- `POST /api/chat` → `ChatController@handle` (মূল chat endpoint)
- `GET /api/customers/{id}` → Customer data দেখায় (resource URL)

---

### Layer 2: Database (ডাটা কোথায় থাকবে)

#### `database/migrations/..._create_customers_table.php`

```
কাজ: customers table তৈরি করে
Columns: id, name, email (unique), phone, address (nullable), timestamps, soft_deletes
```

**কেন soft_deletes?** — Delete করলে সাথে সাথে মুছে যায় না। `deleted_at` column এ date বসে। পরে recover করা যায়। Safety feature।

**কেন email unique?** — একই email দিয়ে দুইবার customer create করা যাবে না। Duplicate prevention।

#### `database/migrations/..._create_chat_logs_table.php`

```
কাজ: প্রতিটা chat interaction log রাখে — success হোক কিংবা fail
Columns: id, user_message, ai_raw_response, parsed_action, status (success/failed/invalid), error_message, timestamps
```

**কেন দরকার?** — Debugging! কোনো সমস্যা হলে দেখতে পারবে — user কী বলেছিল, AI কী respond করেছে, কোথায় fail হয়েছে।

#### `app/Models/Customer.php`

```
কাজ: customers table এর PHP representation
Features: fillable fields (mass assignment protection), SoftDeletes trait
Connection: CustomerService এই model ব্যবহার করে CRUD করে
```

#### `app/Models/ChatLog.php`

```
কাজ: chat_logs table এর PHP representation
Connection: ChatController প্রতিটা request এ এটা ব্যবহার করে log রাখে
```

---

### Layer 3: DTOs — Data Carriers (ডাটা বহনকারী)

#### `app/DTOs/AIResponseDTO.php`

```
কাজ: AI যে response পাঠায়, সেটাকে typed PHP object এ রাখে

Properties:
  - valid (bool)     → AI কি ঠিকমতো বুঝেছে?
  - action (string)  → কী করতে হবে? (create_customer, update_customer, etc.)
  - data (array)     → কী ডাটা extract করেছে? (name, email, phone, address)
  - error (string)   → ভুল হলে কেন ভুল?

কে বানায়: AIResponseParser
কে ব্যবহার করে: ChatController → ActionDispatcher
```

**কেন DTO ব্যবহার করি?** — Array দিয়ে ডাটা pass করলে ভুল হওয়ার সম্ভাবনা বেশি। DTO ব্যবহার করলে IDE autocomplete দেয়, type checking হয়, ভুল ধরা পড়ে।

#### `app/DTOs/ActionResultDTO.php`

```
কাজ: Action handler এর result কে typed object এ রাখে

Properties:
  - success (bool)       → কাজ কি সফল হয়েছে?
  - message (string)     → কী হয়েছে সেটার description
  - resourceUrl (string) → নতুন resource এর URL (e.g., /api/customers/1)
  - data (array)         → result data (customer info)

কে বানায়: Action Handlers (CreateCustomerHandler, etc.)
কে ব্যবহার করে: ChatController → ChatResponseResource
```

---

### Layer 4: Enums

#### `app/Enums/ActionType.php`

```
কাজ: সব allowed action এর list রাখে

Values:
  - CREATE_CUSTOMER = 'create_customer'
  - UPDATE_CUSTOMER = 'update_customer'
  - DELETE_CUSTOMER = 'delete_customer'
  - READ_CUSTOMER   = 'read_customer'

কে ব্যবহার করে: ActionServiceProvider — handler register করতে
```

**কেন Enum?** — String typo হলে (যেমন `'create_custmer'`) PHP compile time এ ধরে ফেলে। Enum ছাড়া runtime এ bug হতো।

---

### Layer 5: AI Service Layer (AI এর সাথে কথা বলা)

#### `app/Services/AI/SystemPromptBuilder.php`

```
কাজ: AI কে STRICT instruction দেয় — "তুমি শুধু JSON return করবে, অন্য কিছু না"

কে এটাকে call করে: AIService
কখন: প্রতিটা AI API call এর সময়
```

**কী বলে AI কে?**
1. তুমি শুধু JSON return করবে — কোনো explanation না
2. এই actions গুলো allowed: create_customer, update_customer, delete_customer, read_customer
3. এই format এ JSON দিবে: `{ "action": "...", "data": {...}, "valid": true }`
4. বুঝতে না পারলে: `{ "valid": false, "error": "reason" }`
5. যেটা user বলেনি সেটা guess করো না

#### `app/Services/AI/AIService.php`

```
কাজ: hellozed AI API কে HTTP call করে, response ফেরত আনে

কে এটাকে call করে: ChatController
কাকে call করে: SystemPromptBuilder (prompt তৈরি করতে), HTTP client (API call করতে)
কী return করে: AI response string (JSON as text)
```

**কিভাবে কাজ করে?**
1. `SystemPromptBuilder` থেকে system prompt নেয়
2. Laravel `Http::post()` দিয়ে AI API call করে
3. Bearer token header যোগ করে (authentication)
4. Timeout set করে (30 seconds)
5. Retry logic আছে (fail হলে 2 বার আবার try করে)
6. API response এর `response` field extract করে return করে

**কেন `withoutVerifying()`?** — Local development এ SSL certificate error হয়। Production এ এটা বাদ দিতে হবে এবং PHP তে proper CA bundle setup করতে হবে।

#### `app/Services/AI/AIResponseParser.php`

```
কাজ: AI এর raw response (string) কে safely parse করে AIResponseDTO তে রাখে

কে এটাকে call করে: ChatController
কাকে call করে: কাউকে না — শুধু নিজের কাজ করে
কী return করে: AIResponseDTO
কখন error throw করে: JSON invalid হলে, required fields missing হলে
```

**কিভাবে কাজ করে?**
1. AI মাঝে মাঝে JSON কে markdown code block এ wrap করে (```` ```json ... ``` ````)। এটা strip করে।
2. `json_decode` করে
3. `valid` field আছে কিনা check করে
4. `valid: false` হলে → error DTO return করে
5. `valid: true` হলে → action আর data validate করে → success DTO return করে

---

### Layer 6: Action Dispatch (কোন কাজ কে করবে)

#### `app/Services/Actions/Contracts/ActionHandlerInterface.php`

```
কাজ: সব handler এর blueprint/contract
Method: execute(array $data): ActionResultDTO

কেন দরকার: সব handler একই interface follow করবে। ActionDispatcher জানে — আমি যেকোনো handler কে execute() call করতে পারি।
```

#### `app/Services/Actions/ActionDispatcher.php`

```
কাজ: AI যে action বলেছে, সেটার জন্য সঠিক handler খুঁজে বের করে call করে

কে এটাকে call করে: ChatController
কাকে call করে: সঠিক Handler class (CreateCustomerHandler, etc.)
কখন error throw করে: Unknown action হলে (InvalidActionException)
```

**কিভাবে কাজ করে?**
1. একটা map আছে: `'create_customer' → CreateCustomerHandler::class`
2. AI বলেছে `action: "create_customer"` → map থেকে handler class খুঁজে বের করে
3. Laravel container দিয়ে handler instance বানায়
4. `handler->execute(data)` call করে
5. result return করে

#### `app/Services/Actions/Handlers/CreateCustomerHandler.php` (উদাহরণ)

```
কাজ: Customer create করে — validate + save

কে এটাকে call করে: ActionDispatcher
কাকে call করে: CustomerService
```

**কিভাবে কাজ করে?**
1. Laravel Validator দিয়ে data validate করে:
   - name → required, string
   - email → required, valid email, unique
   - phone → required, string
   - address → optional
2. Validation fail → error result return
3. Pass → `CustomerService::create()` call করে
4. Success result return করে (customer data + resource URL)

**অন্য handlers (Update, Delete, Read) একই pattern follow করে — শুধু কাজ আলাদা।**

#### `app/Providers/ActionServiceProvider.php`

```
কাজ: Action string গুলোকে Handler class এর সাথে register করে

কখন চলে: App boot হওয়ার সময় (automatically)
কেন আলাদা Provider: Clean separation। নতুন action যোগ করতে শুধু এখানে একটা line add করলেই হবে।
```

Register করে:
```
'create_customer' → CreateCustomerHandler
'update_customer' → UpdateCustomerHandler
'delete_customer' → DeleteCustomerHandler
'read_customer'   → ReadCustomerHandler
```

---

### Layer 7: Customer Service (Database CRUD)

#### `app/Services/Customer/CustomerService.php`

```
কাজ: Customer model এর উপর pure CRUD operations

Methods:
  - create(array)      → নতুন customer save
  - update(id, array)  → existing customer update
  - delete(id)         → soft delete
  - find(id)           → ID দিয়ে খোঁজা
  - findByEmail(email) → Email দিয়ে খোঁজা

কে এটাকে call করে: Action Handlers
কাকে call করে: Customer Model (Eloquent)
```

**কেন আলাদা service?** — Handler এ সরাসরি `Customer::create()` লিখলেও কাজ হতো। কিন্তু আলাদা service রাখলে:
- একই CRUD logic অনেক জায়গায় reuse করা যায়
- Testing সহজ হয়
- Handler শুধু validation ও decision নেয়, database কাজ service করে (Single Responsibility)

---

### Layer 8: HTTP Layer (User facing)

#### `app/Http/Requests/ChatMessageRequest.php`

```
কাজ: User এর input validate করে (controller এ ঢোকার আগেই)

Rules:
  - message → required, string, max 1000 characters

কখন চলে: POST /api/chat request আসলে সবার আগে
Fail হলে: 422 error response (controller পর্যন্ত যায় না)
```

#### `app/Http/Controllers/Api/ChatController.php`

```
কাজ: 🎯 MAIN ORCHESTRATOR — পুরো flow manage করে

এটা নিজে কোনো business logic রাখে না।
শুধু step-by-step অন্যদের call করে:
  1. AIService::chat() → AI call
  2. AIResponseParser::parse() → parse response
  3. ActionDispatcher::dispatch() → correct handler এ পাঠায়
  4. ChatLog::create() → log রাখে
  5. ChatResponseResource → response format করে return

Error handling: try-catch দিয়ে সব ধরনের error gracefully handle করে
```

#### `app/Http/Resources/ChatResponseResource.php`

```
কাজ: সব API response কে consistent format এ রাখে

Format:
{
  "success": true/false,
  "message": "...",
  "data": {...} or null,
  "url": "/api/customers/1" or null
}

Methods:
  - fromResult(ActionResultDTO) → success/fail response
  - error(message, status) → error response
```

#### `app/Http/Resources/CustomerResource.php`

```
কাজ: Customer model কে clean JSON format এ convert করে
ব্যবহার: GET /api/customers/{id} endpoint এ
```

---

### Layer 9: Exceptions (Error Classes)

#### `app/Exceptions/AIResponseException.php`

```
কাজ: AI সংক্রান্ত error situations handle করে
কখন throw হয়: AI response parse করতে fail হলে, API timeout হলে
কে throw করে: AIService, AIResponseParser
কে catch করে: ChatController → 502 status return করে
```

#### `app/Exceptions/InvalidActionException.php`

```
কাজ: Unknown action error handle করে
কখন throw হয়: AI যে action বলেছে সেটা system এ register নেই
কে throw করে: ActionDispatcher
কে catch করে: ChatController → 422 status return করে
```

---

## 6. File Connection Map — কে কাকে call করে

```
Request আসে →

routes/api.php
  └─→ ChatController::handle()
        │
        ├─→ ChatMessageRequest (auto-validates "message" field)
        │
        ├─→ AIService::chat(userMessage)
        │     └─→ SystemPromptBuilder::build()
        │     └─→ Http::post() → hellozed AI API
        │     └─→ returns AI response string
        │
        ├─→ AIResponseParser::parse(rawResponse)
        │     └─→ returns AIResponseDTO
        │
        ├─→ ActionDispatcher::dispatch(aiResponseDTO)
        │     └─→ ActionServiceProvider (boot time registration)
        │     │     └─→ 'create_customer' → CreateCustomerHandler
        │     │     └─→ 'update_customer' → UpdateCustomerHandler
        │     │     └─→ 'delete_customer' → DeleteCustomerHandler
        │     │     └─→ 'read_customer'   → ReadCustomerHandler
        │     │
        │     └─→ CreateCustomerHandler::execute(data)
        │           └─→ Validator::make() (Laravel validation)
        │           └─→ CustomerService::create(validatedData)
        │                 └─→ Customer::create() (Eloquent → MySQL)
        │           └─→ returns ActionResultDTO
        │
        ├─→ ChatLog::create() (audit log — সবসময়)
        │
        └─→ ChatResponseResource::fromResult() → JSON response

← Response যায়
```

### Dependency Diagram (কে কার উপর নির্ভরশীল):

```
ChatController
  ├── depends on → AIService
  │                  └── depends on → SystemPromptBuilder
  ├── depends on → AIResponseParser
  ├── depends on → ActionDispatcher
  │                  └── depends on → Handlers
  │                                    └── depends on → CustomerService
  │                                                       └── depends on → Customer (Model)
  ├── depends on → ChatLog (Model)
  └── depends on → ChatResponseResource
```

---

## 7. Step-by-Step Development Guide

> এটা হলো যদি তুমি শূন্য থেকে এই project build করতে চাও, তাহলে কোন step এর পরে কোনটা করতে হবে।

### Step 1: Project তৈরি করো

```bash
composer create-project laravel/laravel ai-crud-chatbot
cd ai-crud-chatbot
```

**কেন প্রথমে?** — সব কিছুর আগে Laravel project structure দরকার।

### Step 2: Database setup করো

1. MySQL এ database তৈরি করো: `CREATE DATABASE ai_crud_chatbot;`
2. `.env` ফাইলে DB credentials দাও
3. AI API config `.env` তে যোগ করো

**কেন?** — Database ছাড়া migration চলবে না, config ছাড়া API call হবে না।

### Step 3: Config file বানাও

`config/ai.php` তৈরি করো — API URL, key, timeout রাখো।

**কেন?** — Services এই config থেকে values নেবে। Hardcode করা unsafe।

### Step 4: Database tables design করো (Migrations)

1. `customers` table migration তৈরি করো
2. `chat_logs` table migration তৈরি করো
3. `php artisan migrate` চালাও

**কেন এখন?** — Models বানাতে হলে tables আগে define করা দরকার।

### Step 5: Models বানাও

1. `Customer` model — fillable, SoftDeletes
2. `ChatLog` model — fillable

**কেন?** — Database এর সাথে interact করতে Model লাগবে।

### Step 6: DTOs বানাও

1. `AIResponseDTO` — AI response ধরে রাখবে
2. `ActionResultDTO` — action result ধরে রাখবে

**কেন?** — Services গুলোর মধ্যে ডাটা pass করতে typed objects দরকার। Array দিয়ে করলে bug হওয়ার সম্ভাবনা বেশি।

### Step 7: Enum বানাও

`ActionType` enum — allowed actions define করো।

**কেন?** — String typo prevention। IDE support।

### Step 8: Exceptions বানাও

1. `AIResponseException` — AI error handle করতে
2. `InvalidActionException` — unknown action handle করতে

**কেন?** — Generic PHP exceptions দিয়ে করলে কোথায় error হয়েছে বুঝা যায় না। Custom exceptions specific error handling enable করে।

### Step 9: AI Service Layer বানাও (গুরুত্বপূর্ণ!)

1. **প্রথমে** `SystemPromptBuilder` — AI কে কী instruction দেবে সেটা ঠিক করো
2. **তারপর** `AIService` — SystemPromptBuilder ব্যবহার করে AI API call করো
3. **তারপর** `AIResponseParser` — AI response parse করার logic লেখো

**কেন এই order?**
- AIService → SystemPromptBuilder দরকার (prompt বানাতে)
- Parser independent — কারো উপর depend করে না

### Step 10: Customer Service বানাও

`CustomerService` — pure CRUD methods। Handlers এটা ব্যবহার করবে।

**কেন এখন?** — Handlers বানানোর আগে service দরকার।

### Step 11: Action Handlers বানাও

1. **প্রথমে** `ActionHandlerInterface` — blueprint বানাও
2. **তারপর** `CreateCustomerHandler` — সবচেয়ে basic handler
3. **তারপর** `UpdateCustomerHandler`, `DeleteCustomerHandler`, `ReadCustomerHandler`

**কেন Interface আগে?** — সব handler একই contract follow করবে। Contract ছাড়া handler বানালে inconsistency হবে।

### Step 12: ActionDispatcher বানাও

Action string → handler routing logic।

**কেন handlers এর পরে?** — Dispatcher handlers কে call করে → handlers আগে ready থাকতে হবে।

### Step 13: ActionServiceProvider বানাও

Handler registration। `bootstrap/providers.php` এ register করো।

**কেন?** — Dispatcher কে বলে দিতে হবে কোন action কোন handler handle করবে। Provider এটা app boot হওয়ার সময় setup করে।

### Step 14: HTTP Layer বানাও

1. `ChatMessageRequest` — input validation
2. `ChatResponseResource` — response formatting
3. `CustomerResource` — customer data formatting
4. `ChatController` — পুরো flow orchestrate করো

**কেন সবার শেষে?** — Controller সবকিছুর উপর depend করে। আগে সব component ready না হলে controller import-ই করতে পারবে না।

### Step 15: Routes register করো

`routes/api.php` এ routes define করো। `bootstrap/app.php` এ api routes enable করো।

### Step 16: Test চালাও

```bash
php artisan test
```

---

## 8. Database Design

### customers table

| Column     | Type         | Constraints     | Description                    |
|-----------|-------------|-----------------|-------------------------------|
| id        | bigint      | Primary Key, Auto | Unique identifier             |
| name      | varchar(255)| NOT NULL         | Customer full name             |
| email     | varchar(255)| UNIQUE, NOT NULL | Email — duplicate proof        |
| phone     | varchar(255)| NOT NULL         | Phone number                   |
| address   | varchar(255)| NULLABLE         | Address (optional)             |
| created_at| timestamp   | Auto             | কখন create হয়েছে              |
| updated_at| timestamp   | Auto             | কখন last update হয়েছে         |
| deleted_at| timestamp   | NULLABLE         | Soft delete timestamp          |

### chat_logs table

| Column          | Type                           | Description                    |
|----------------|-------------------------------|-------------------------------|
| id             | bigint                         | Primary Key                    |
| user_message   | text                           | User কী বলেছে                  |
| ai_raw_response| text (nullable)                | AI কী response দিয়েছে          |
| parsed_action  | varchar (nullable)             | কোন action detect হয়েছে       |
| status         | enum(success, failed, invalid) | কী হয়েছে                       |
| error_message  | text (nullable)                | Error হলে কেন                   |
| created_at     | timestamp                      | কখন                             |
| updated_at     | timestamp                      | কখন                             |

---

## 9. API Endpoints

### `POST /api/chat` — Main Chat Endpoint

**Request:**
```json
{
  "message": "Customer Entry: Naeem Sarker, naeem@gmail.com, 01678789233, Uttara, Dhaka"
}
```

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Customer created successfully.",
  "data": {
    "id": 1,
    "name": "Naeem Sarker",
    "email": "naeem@gmail.com",
    "phone": "01678789233",
    "address": "Uttara, Dhaka"
  },
  "url": "/api/customers/1"
}
```

**Validation Error (422):**
```json
{
  "success": false,
  "message": "The email has already been taken.",
  "data": null,
  "url": null
}
```

**AI Error (502):**
```json
{
  "success": false,
  "message": "AI returned an invalid response.",
  "data": null,
  "url": null
}
```

### `GET /api/customers/{id}` — Get Customer

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "Naeem Sarker",
    "email": "naeem@gmail.com",
    "phone": "01678789233",
    "address": "Uttara, Dhaka"
  }
}
```

---

## 10. AI Integration

### AI API Details

- **URL:** `https://ai.hellozed.com/api/zedbot/chat`
- **Method:** POST
- **Auth:** Bearer token (API key)
- **Request body:** `{ "message": "...", "system_prompt": "..." }`
- **Response structure:** `{ "response": "<AI content>", "success": true, "usage": {...} }`

### System Prompt (AI কে যা বলা হয়)

AI কে strict instruction দেওয়া হয়:
1. **শুধু JSON return করো** — কোনো explanation না
2. **এই actions allowed:** create_customer, update_customer, delete_customer, read_customer
3. **Success format:** `{ "action": "...", "data": {...}, "valid": true }`
4. **Error format:** `{ "valid": false, "error": "reason" }`
5. **Guess করো না** — user যা বলেনি সেটা add করো না

### AI Response কিভাবে process হয়

```
AI returns: "```json\n{\"action\":\"create_customer\",...}\n```"
              ↓
AIService extracts: the "response" field from API JSON
              ↓
AIResponseParser strips: markdown code fences (```)
              ↓
AIResponseParser decodes: JSON to PHP array
              ↓
AIResponseParser validates: valid, action, data fields exist
              ↓
Returns: AIResponseDTO (typed object)
```

---

## 11. Error Handling Strategy

### Error Layers (তিন স্তরে error ধরা হয়):

```
Layer 1: ChatMessageRequest
  ├── Message খালি? → 422 (controller পর্যন্ত যায় না)
  └── Message > 1000 chars? → 422

Layer 2: AI Response
  ├── AI API down/timeout → AIResponseException → 502
  ├── AI invalid JSON দিলো → AIResponseException → 502
  └── AI বলেছে valid: false → 422 with AI error message

Layer 3: Action Handler
  ├── Laravel Validator fail → 422 (email taken, name missing, etc.)
  ├── Customer not found → 422
  └── Unknown action → InvalidActionException → 422

Layer 4: Catch-all
  └── যেকোনো unexpected error → 500 "An unexpected error occurred."
```

### কেন এতো layer?

প্রতিটা layer এ error ধরলে user কে specific error message দেওয়া যায়। একটাই try-catch দিলে সব error "Something went wrong" বলতো — debugging impossible।

### সব interaction log হয়

Success হোক কিংবা fail — `chat_logs` table এ সবসময় entry যায়। কোনোদিন কিছু ভুল হলে এই log দেখে বুঝতে পারবে ঠিক কোথায় সমস্যা।

---

## 12. Design Patterns Used

### Strategy Pattern (Action Dispatch)

**সমস্যা:** AI যে action বলবে, সেটা অনুযায়ী আলাদা কাজ করতে হবে। if-else দিয়ে করলে:
```php
// ❌ খারাপ approach
if ($action === 'create_customer') { ... }
elseif ($action === 'update_customer') { ... }
elseif ($action === 'delete_customer') { ... }
// নতুন action যোগ করতে হলে এই file edit করতে হবে — ভয়ংকর!
```

**সমাধান:** Strategy Pattern
```php
// ✅ ভালো approach
$dispatcher->register('create_customer', CreateCustomerHandler::class);
$dispatcher->register('update_customer', UpdateCustomerHandler::class);
// নতুন action? শুধু নতুন handler class বানাও + register করো। কোনো existing code change নেই!
```

**সুবিধা:**
- Open/Closed Principle — existing code change না করে নতুন feature যোগ করা যায়
- প্রতিটা handler independently testable
- Clean, readable code

### DTO Pattern (Data Transfer Objects)

**সমস্যা:** Array দিয়ে ডাটা pass করলে:
```php
// ❌ কী আছে array তে? IDE জানে না। Typo হলে runtime error।
$result['sucess']; // typo! but no compile error
```

**সমাধান:** DTO
```php
// ✅ IDE autocomplete দেয়। Typo হলে compile error।
$result->success; // typed, safe
```

### Service Layer Pattern

**সমস্যা:** Controller এ business logic রাখলে controller মোটা হয়, reuse করা যায় না।

**সমাধান:** আলাদা Service class —
- `AIService` → AI call
- `CustomerService` → CRUD
- `ActionDispatcher` → routing

Controller শুধু orchestrate করে — "এই কাজটা AIService করো, এই কাজটা CustomerService করো।"

---

## 13. Setup Instructions

### Prerequisites
- PHP >= 8.2
- Composer
- MySQL

### Installation

```bash
# Clone project
git clone <repo-url> ai-crud-chatbot
cd ai-crud-chatbot

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate
```

### Configuration

Edit `.env`:
```env
# Database
DB_DATABASE=ai_crud_chatbot
DB_USERNAME=root
DB_PASSWORD=your_password

# AI API
AI_API_URL=https://ai.hellozed.com/api/zedbot/chat
AI_API_KEY=your_api_key_here
AI_API_TIMEOUT=30
AI_MAX_RETRIES=2
```

### Database Setup

```bash
php artisan migrate
```

### Run Server

```bash
php artisan serve
# Server starts at: http://127.0.0.1:8000
```

### Run Tests

```bash
php artisan test
# Expected: 41 passed, 0 failures
```

---

## 14. Postman Testing Guide

### Setup
1. Open Postman
2. Base URL: `http://127.0.0.1:8000`
3. Headers (সব POST request এ):
   - `Content-Type: application/json`
   - `Accept: application/json`

### CREATE Tests

**Test 1: Standard English**
```json
{ "message": "Customer Entry: Naeem Sarker, naeem@gmail.com, 01678789233, Uttara, Dhaka" }
```
**Expected:** `200` — Customer created

**Test 2: Bangla**
```json
{ "message": "নতুন কাস্টমার যোগ করো: নাম Karim, ইমেইল karim@gmail.com, ফোন 01712345678, ঠিকানা মিরপুর, ঢাকা" }
```
**Expected:** `200` — Customer created

**Test 3: Casual**
```json
{ "message": "save customer Salam, salam@mail.com, 01999888777, Banani" }
```
**Expected:** `200` — Customer created

### UPDATE Test

```json
{ "message": "Update customer naeem@gmail.com, change phone to 01999999999" }
```
**Expected:** `200` — Customer updated

### READ Tests

```json
{ "message": "Show me the customer naeem@gmail.com" }
```
**Expected:** `200` — Customer found

```json
{ "message": "Show all customers" }
```
**Expected:** `200` — All customers list

### DELETE Test

```json
{ "message": "Delete customer naeem@gmail.com" }
```
**Expected:** `200` — Customer deleted

### ERROR Tests

**Duplicate email:**
```json
{ "message": "Customer Entry: Naeem Sarker, naeem@gmail.com, 01678789233, Dhaka" }
```
**Expected:** `422` — Email taken

**Invalid input:**
```json
{ "message": "Customer Entry: Naeem, wrongemail, 123" }
```
**Expected:** `422` — AI returns invalid

**Empty message:**
```json
{ "message": "" }
```
**Expected:** `422` — Message required

**Gibberish:**
```json
{ "message": "asdfghjkl 12345 random" }
```
**Expected:** `422` — AI returns invalid

**Nonexistent customer (GET):**
`GET http://127.0.0.1:8000/api/customers/999`
**Expected:** `404`

### Recommended Order
Create → Read → Update → Read again → Delete → Error tests

### Verify Logs
```sql
SELECT id, parsed_action, status, error_message, created_at FROM chat_logs;
```

---

## 15. নতুন Entity যোগ করার নিয়ম

ধরো তুমি Product entity যোগ করতে চাও:

### Step 1: Migration + Model
```bash
php artisan make:model Product -m
```
Migration এ columns যোগ করো, model এ fillable set করো।

### Step 2: Service
`app/Services/Product/ProductService.php` তৈরি করো — CRUD methods।

### Step 3: Handlers
`app/Services/Actions/Handlers/` এ তৈরি করো:
- `CreateProductHandler.php`
- `UpdateProductHandler.php`
- `DeleteProductHandler.php`
- `ReadProductHandler.php`

সব handler `ActionHandlerInterface` implement করবে।

### Step 4: Enum update
`ActionType.php` এ নতুন cases যোগ করো:
```php
case CREATE_PRODUCT = 'create_product';
case UPDATE_PRODUCT = 'update_product';
```

### Step 5: Register
`ActionServiceProvider.php` এ register করো:
```php
$dispatcher->register(ActionType::CREATE_PRODUCT->value, CreateProductHandler::class);
```

### Step 6: System Prompt update
`SystemPromptBuilder.php` এ নতুন allowed actions যোগ করো।

### কী change হয় না?
- ❌ ChatController — same
- ❌ AIService — same
- ❌ AIResponseParser — same
- ❌ ActionDispatcher — same
- ❌ Existing Handlers — same

**এটাই Strategy Pattern এর power!**

---

## 16. Golden Rules

1. **🔒 Never trust AI directly** — AI যা বলে সেটা Laravel Validator দিয়ে আবার validate করো
2. **📝 Log everything** — success, fail, invalid — সব chat_logs এ যায়
3. **🛡️ Fail gracefully** — কোনো error এ server crash করবে না, structured JSON error response যাবে
4. **📦 One handler per action** — প্রতিটা handler independent, focused, testable
5. **⚙️ Config over hardcode** — সব settings config/ai.php ও .env এ, code এ hardcode নেই
6. **🧪 Test everything** — 41 automated tests আছে, নতুন feature add করলে test ও লেখো
7. **🔄 Soft deletes** — Customer delete করলে recover করা যায়
8. **📐 Strategy Pattern** — নতুন entity/action যোগ করতে existing code change করো না
