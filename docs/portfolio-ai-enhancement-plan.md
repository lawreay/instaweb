# Portfolio AI Enhancement Plan

## Goal

Add an **AI Enhance** tool to the Portfolio Advanced Editor. It will help an owner rewrite existing portfolio text and propose new, structured entries for their portfolio. Nothing is saved or published until the owner reviews and accepts it.

The feature will support both **OpenAI** and **Google Gemini** behind one provider interface. The platform administrator selects the default provider and model; a portfolio owner may select an enabled provider when using their own key.

## User experience

### Entry point

Add an **AI Enhance** button beside **Manage site** and **Preview** in `app/Views/portfolio/advanced.php`.

Selecting it opens an accessible modal or right-side panel with these choices:

1. **Improve writing** — rewrite a headline, bio, project description, or an existing advanced-editor item.
2. **Create section ideas** — generate new entries for Goals, Interests, Challenges, Experience, Education, Skills, News, Partnerships, CV, or Projects.
3. **Complete my portfolio** — identify incomplete sections and propose a small, user-approved set of entries.

The user chooses tone (for example: professional, confident, friendly), optional instructions, and the number of suggestions where applicable.

### API-key flow

1. The page receives only whether an enabled platform key exists for OpenAI and/or Gemini. It never receives a platform key.
2. If a usable platform key exists, generation starts with the selected/default provider.
3. If none exists, selecting **Generate** opens a key dialog with provider selection and a password-style API-key field.
4. The dialog clearly says: **“Used only for this request and not saved.”**
5. The key is sent to the server over the authenticated request, used once, then discarded. It is not written to the database, session, HTML, JavaScript storage, logs, toast messages, or audit records.

An invalid key, provider outage, quota error, or timeout results in a clear retry message without exposing provider error details or secrets.

### Review before change

AI returns a proposal, never a direct update:

- A rewrite shows current and proposed text, with **Replace** and **Keep current** actions.
- Generated entries show selectable cards; the user can edit each proposal before adding it.
- **Apply selected** creates or updates only the accepted records.
- The standard editor remains usable without AI.

## Architecture

### Provider abstraction

Create a provider-neutral contract, for example:

```php
interface PortfolioAiProvider
{
    public function propose(PortfolioAiRequest $request, string $apiKey): PortfolioAiProposal;
}
```

Implement:

- `OpenAiPortfolioProvider` — use the OpenAI Responses API with structured output for predictable proposal JSON. The OpenAI structured-output guide supports schema-constrained model output, which is appropriate for mapping suggestions into the portfolio data model. [OpenAI structured outputs documentation](https://developers.openai.com/api/docs/guides/structured-outputs)
- `GeminiPortfolioProvider` — map the same neutral request and response schema to Gemini's generation API.
- `PortfolioAiService` — chooses the provider, builds portfolio context, validates output, applies rate limits, and returns a neutral proposal to the controller.

Provider-specific request/response code must stay out of controllers and views. Models are configuration values, not hard-coded in templates.

### Configuration and secret precedence

Add an `app/Config/ai.php` configuration file backed by environment variables:

```dotenv
PORTFOLIO_AI_ENABLED=1
PORTFOLIO_AI_DEFAULT_PROVIDER=openai
OPENAI_API_KEY=
OPENAI_MODEL=
GEMINI_API_KEY=
GEMINI_MODEL=
```

Precedence for a generation request:

1. A platform key for the selected enabled provider.
2. A user-entered, one-request key.
3. Otherwise return `key_required` and open the key dialog.

Do not store production provider keys in `platform_settings`; use `.env` or the deployment secret manager. An optional admin settings screen may select the default provider/model and enable or disable providers, but must only expose key-presence indicators.

### Server endpoints

Add owner-scoped, CSRF-protected JSON routes:

| Route | Purpose |
| --- | --- |
| `GET /dashboard/websites/portfolio/ai/status?id={websiteId}` | Returns enabled providers and key availability only. |
| `POST /dashboard/websites/portfolio/ai/propose` | Validates ownership, receives one enhancement request, calls the provider, and returns a proposal. |
| `POST /dashboard/websites/portfolio/ai/apply` | Validates and atomically applies user-selected proposal entries. |

`PortfolioAiController` must load the portfolio and all relevant content server-side after verifying the website belongs to the authenticated user. The browser may identify a target section/item, but must never supply a different portfolio's source data or database identifiers as trusted context.

### Proposal contract

Use a strict, provider-neutral JSON shape. All output is plain text; generated HTML, scripts, links with unsafe schemes, and arbitrary section keys are rejected.

```json
{
  "operation": "rewrite|suggest_items|complete_portfolio",
  "summary": "Short explanation of the proposal",
  "rewrites": [
    {"target": "bio", "text": "Proposed replacement"}
  ],
  "items": [
    {
      "section_key": "skills",
      "title": "Financial analysis",
      "payload": {"category": "Accounting", "proficiency": "Advanced"}
    }
  ]
}
```

Before returning or applying a proposal, validate:

- Target section is in `AdvancedPortfolioController::SECTIONS`.
- Every item uses the same field allowlist and limits as the existing Advanced Editor.
- Requested item count and text length are capped.
- URLs, if a section permits them, use the application's existing URL validation.
- Rewrites can only target the owner’s existing profile/item fields.

## Prompt and data rules

- Send the minimum portfolio context required for the selected task: owner-entered content, chosen section, tone, and request instructions.
- Treat all portfolio content and user instructions as untrusted data, clearly delimited from system instructions.
- Instruct providers not to invent qualifications, employers, dates, awards, or contact details. Suggestions must be framed from facts the user provides; missing facts become editable placeholders or follow-up questions.
- Do not send password, payment, session, internal-path, API-key, or unrelated account data to either provider.
- Add a visible note that generated content requires user review for accuracy.

## Persistence, privacy, and safety

Create a minimal `portfolio_ai_usage` table or equivalent audit facility containing:

- user ID, website ID, provider, configured model label, operation, status, request timestamp, and response timestamp;
- token/cost metadata when the provider returns it;
- no API key, raw prompt, or raw generated portfolio content by default.

Enforce per-user and per-website generation limits, input/output size limits, CSRF checks, ownership checks, and sensible HTTP timeouts. Redact provider authorization headers and user-supplied keys from all logs and exceptions.

## Implementation sequence

1. **Configuration and contracts**
   - Add `ai.php`, environment examples, provider interface, request/proposal DTOs, schema validation, and provider error types.
   - Add settings/migration for feature enablement, provider availability, and usage auditing.

2. **Server integration**
   - Implement OpenAI and Gemini adapters plus `PortfolioAiService`.
   - Add `PortfolioAiController` and the status/propose/apply routes.
   - Add ownership, CSRF, rate-limit, payload-size, timeout, and output-validation tests.

3. **Advanced Editor UI**
   - Add the top-right **AI Enhance** button shown in the supplied design reference.
   - Build the task chooser, key-required dialog, loading/error states, review cards, and accessible focus management.
   - Keep all changes in draft until the user explicitly applies them.

4. **Enhancement flows**
   - Ship rewrite support for headline, bio, project details, and advanced-item details first.
   - Add structured section-entry generation and multi-item review/apply second.
   - Add completeness suggestions last; it must only propose data, never create it automatically.

5. **Verification and rollout**
   - Unit-test each provider adapter with mocked HTTP responses.
   - Test both configured-key and no-key/modal paths, including rejected or expired keys.
   - Test prompt-injection-like portfolio text, malformed output, invalid sections, cross-owner requests, rate limits, and no-secret logging.
   - Run PHP lint, the portfolio integration check, and browser checks for desktop/mobile editor behavior.
   - Launch behind `PORTFOLIO_AI_ENABLED`, then review usage/error metrics before enabling it broadly.

## Acceptance criteria

- An owner can open **AI Enhance** from the Advanced Editor.
- An owner can safely rewrite existing text and review the diff before saving.
- An owner can generate, edit, select, and add valid new portfolio entries.
- A missing platform key opens a provider/key dialog; the entered key is not persisted.
- Existing platform keys never reach browser HTML, JavaScript, or API responses.
- AI cannot write to another owner’s portfolio or bypass existing validation.
- The editor continues to work normally with AI disabled or a provider unavailable.

## Decision to confirm before implementation

Implement both providers behind the shared interface, with **OpenAI as the recommended initial platform default** and Gemini available as an administrator-enabled alternative. This avoids coupling the product to a single provider while keeping the first release focused. The only product decision needed before building is whether owners should be allowed to bring a one-request key, as designed above, or whether AI should be available only when an administrator configures a platform key.
