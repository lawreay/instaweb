# Portfolio AI Enhance: Setup and Workflow

## What the feature does

AI Enhance is an optional writing assistant inside the Portfolio Advanced Editor. It supports OpenAI and Google Gemini through a provider-neutral PHP service. It can:

- Rewrite a profile headline or biography.
- Rewrite an existing written portfolio entry.
- Propose new entries for supported portfolio sections.

AI output is a draft. The user reviews and edits the text, selects which generated entries to keep, and explicitly applies the approved proposal. Closing the dialog or generating another proposal does not save the current proposal.

## Request flow

1. The authenticated owner opens **Portfolio Studio → Advanced Editor → AI Enhance**.
2. The editor shows the configured default provider and whether a platform key is available. It never receives the platform key itself.
3. The owner chooses an operation, target, tone, and optional instructions.
4. The browser sends the request and CSRF token to the owner-scoped proposal endpoint.
5. The controller verifies website ownership, checks the per-user rate limit, and loads the portfolio context from the database.
6. `PortfolioAiService` validates the provider and key, builds a bounded prompt, and calls the selected adapter through `PortfolioAiHttpClient`.
7. The provider returns structured JSON. The service normalises the text and caps the number and size of proposed items.
8. The browser renders an editable review. Generated entries can be selected individually; rewrites can be edited before applying.
9. Applying sends only the edited proposal to the apply endpoint. The controller validates the operation, target and fields again, then writes approved changes to the current owner's portfolio.
10. The editor returns to the affected section. If generation or apply fails, the status area displays a safe error and the action can be retried.

## Environment configuration

Set these values in the server's private environment file. Never commit real API keys.

```dotenv
PORTFOLIO_AI_ENABLED=1
PORTFOLIO_AI_DEFAULT_PROVIDER=gemini
PORTFOLIO_AI_ALLOW_REQUEST_KEY=1
OPENAI_API_KEY=
OPENAI_MODEL=gpt-5-mini # OpenAI API usage is generally billed separately
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.5-flash # Confirm free-tier availability and quota for your API project
```

- Set `PORTFOLIO_AI_ENABLED=0` to disable the feature.
- `PORTFOLIO_AI_DEFAULT_PROVIDER` accepts `openai` or `gemini`; the application defaults to Gemini for a free-tier-first setup.
- A free ChatGPT plan does not include free OpenAI API calls. For a no-cost setup, create a Gemini API key and verify the selected model is available under your Google AI Studio project’s current free tier. Free-tier quotas and model access can change.
- Configure the platform key for the provider you want to offer. The key stays server-side.
- If request-only keys are allowed, a user can paste a provider key for one request. It is cleared from the form after the request and is not intentionally persisted.
- PHP cURL must be enabled and the server must be able to reach the provider over HTTPS.
- Model names are configurable; confirm that the selected model is available to the configured account.

## Failure handling

| Symptom | Checks |
| --- | --- |
| AI is disabled | Confirm `PORTFOLIO_AI_ENABLED` is enabled and reload the editor. |
| Key required | Configure the selected provider key or use a request-only key if permitted. |
| Authentication error | Check that the API key belongs to the selected provider and has not been revoked. |\n| Rate limit or quota error | Wait for quota to reset or check the provider's current free-tier limits. |\n| Model or request rejected | Confirm the model name and its support for structured JSON output. |
| Request timed out | Check outbound HTTPS connectivity, cURL, provider health, and server timeout settings. |
| Suggestions are invalid | Retry with shorter, more specific instructions. The editor validates required fields before saving. |
| Apply fails | Keep the draft text, verify the entry still exists, and retry. Nothing is saved by generation alone. |

Provider response bodies and API keys must not be surfaced to the browser or written to logs. Do not log raw portfolio prompts or generated content.

## Verification checklist

- [ ] Open AI Enhance as an authenticated portfolio owner.
- [ ] Confirm provider status never reveals a platform key.
- [ ] Test a configured OpenAI key and, separately, a configured Gemini key.
- [ ] Test the request-only key path when enabled; verify the field clears after the request.
- [ ] Generate a profile rewrite, edit it, and apply it.
- [ ] Generate section entries, deselect one, edit another, and apply; only selected entries should be created.
- [ ] Try an empty rewrite and verify the UI blocks applying it.
- [ ] Test invalid keys, provider errors, missing cURL, and timeout handling; confirm loading state always clears.
- [ ] Confirm another user's website ID cannot be used to read, propose for, or apply to their portfolio.
- [ ] Verify CSRF and rate-limit responses.
- [ ] Run PHP lint and the portfolio integration checks, then exercise the flow in desktop and mobile browsers.

## Current boundaries

AI does not invent facts intentionally, but model output still requires human review. Generated suggestions are not a substitute for checking dates, qualifications, links, or claims. The standard editor remains usable when AI is disabled or unavailable.
