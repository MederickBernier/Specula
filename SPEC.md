# Specula — Project Spec (v3: Laravel Pivot)

*(working title: "TechLead Central"; name chosen: Specula, Latin for watchtower/lookout — also the root of "speculate," a fitting double meaning for the prototype module)*

**Changelog from v2:** v2 targeted ASP.NET Core + Blazor/SPA on .NET 10, blocked in practice by Visual Studio 2026 tooling friction. This version supersedes the stack choice only — the web-app-over-desktop pivot, the module scope, the full data model, and the deployment target (DO droplet, self-hosted Postgres in Docker) all carry forward unchanged. What changes is language/framework: **Laravel (PHP)** in place of ASP.NET Core, chosen on the strength of deep existing familiarity (ruled out as a factor in v1's original C# vs. PHP comparison, but back in play now that the .NET tooling itself became the actual blocker) and **Laravel Sail** for a fast, low-friction local Docker setup.

**Changelog from v1:** see v2 for the desktop → web pivot; unaffected by this version.

## Purpose

Unchanged: a personal instrumentation tool for the Tech Lead role, not a team tool. GitHub already covers code review and task management; this stays scoped to decisions, vetting, prototypes, tech currency, and security posture.

## Scope Boundary

Unchanged.
- **In scope:** personal record-keeping and workflow for technical leadership work.
- **Out of scope:** code review, team task/sprint management, anything GitHub already does.

## Core Modules

Unchanged — Decision Records, Feasibility/Vetting Log, Prototype/Spike Tracker, Tech Radar/Currency Tracker, Security Posture Notes. See Data Model section (structure carries over as-is; implementation shifts from EF Core entities to Eloquent models — see note below).

## Stack Decision (superseding v2)

**Backend: Laravel**

- Chosen over continuing with ASP.NET Core purely on friction grounds — VS 2026's rough edges made the .NET path a worse use of time than the deep, ~20-year PHP familiarity already on hand. Not a technical reversal of v2's reasoning, a practical one: tooling pain is a real cost.
- **Repository pattern note:** v1/v2's repository-interface discipline (`IDecisionRepository`, etc.) was originally reasoned against EF Core, where it's a natural fit. Laravel's idiomatic default is direct Eloquent model usage, not a repository layer — as flagged back when Laravel was first considered as an option. Two paths:
  - **Drop the explicit interface layer, lean on Eloquent directly** — less ceremony, more idiomatic Laravel, but loses the "swap the data layer without touching callers" property the interfaces gave v1/v2. Given the app is staying on Postgres either way (no SQLite→Postgres swap left to do, unlike v1→v2), this property matters less than it did originally.
  - **Keep explicit repository classes wrapping Eloquent** — preserves the pattern and the discipline, costs a bit of Laravel-idiom friction.
  - **Recommendation:** drop the explicit interfaces for v3. The reason they existed (cheap SQLite → Postgres swap) already happened in v2 and won't happen again — Eloquent models directly, keeping controllers/Livewire components thin, is the more proportionate choice now.

**Local dev environment: Laravel Sail**

- Sail's own `docker-compose.yml` handles the local Postgres container and app container together — replaces the earlier hand-assembled compose file for local dev. `./vendor/bin/sail up` in place of the separate `dotnet run` + standalone Postgres container split from v2.
- **Note for later:** Sail's compose setup is tuned for local development (bind-mounted source, dev-oriented settings), not production. The production `docker-compose.yml` for the DO droplet (see Deployment below) will still be hand-written, separate from Sail's — Sail gets you fast local iteration, not the deployment artifact.

**Frontend: React via Inertia**

- Resolved (was left open pending markdown-editor prototyping). Decided in favor of Inertia+React over Livewire for a reason beyond this project alone: next-gen work is expected to use React, so building Specula against it doubles as deliberate hands-on practice on a stack you'll need regardless — the Tech Radar/currency-tracking instinct this whole app is built around, applied to your own React skills.
- Worth being explicit about the tradeoff this locks in: Livewire would likely get a working app built faster (no separate JS toolchain, no new component mental model to re-establish) — that speed is being traded for the practice value, not because Inertia+React is a better technical fit for this specific app's needs. A reasonable trade given the stated goal, just not a free one.
- Practical upside this also buys: better markdown-editor ecosystem out of the box (CodeMirror-based editors, `react-markdown`) than Livewire's JS-interop-dependent options — so the decision isn't purely a skills trade, it also resolves the markdown-tooling question in the stronger direction.
- Laravel backend serves as a pure JSON-via-Inertia API layer; React + TypeScript on the frontend, scaffolded via `laravel/breeze --stack=react` (Breeze supports an Inertia+React starter directly, which also folds in the auth scaffolding from the Authentication section below).

**Database: Postgres, self-hosted in Docker — unchanged from v2**

- No change in reasoning: single-user, no concurrent-write contention, DO Managed Databases still not worth the extra cost over self-hosting.
- Migrations move from EF Core migrations to Laravel's native migration system (`php artisan make:migration`) — same schema, different tooling.

**Content requirement: Markdown-native — unchanged**

Markdown-authored fields still need an editor component, a renderer for reading back, and storage as raw markdown in Postgres. Laravel-side rendering options: `league/commonmark` (the library Laravel's own `Str::markdown()` helper wraps) is the natural equivalent to v2's Markdig choice.

## Authentication (revised for Laravel)

Same requirements as v2 — no self-service registration, accounts created only by an admin, real hashed-password login, cookie-based session — but Laravel provides this largely out of the box rather than needing the hand-rolled `User`/`IUserRepository`/`PasswordHasher<TUser>` approach v2 specified for ASP.NET Core.

**Approach: Laravel's built-in auth scaffolding (Breeze), with self-registration removed**

- Laravel ships a `users` table migration, `Hash::make()`/`Hash::check()` (bcrypt by default) and session-based cookie auth in the framework itself — no need to hand-build password hashing or a custom `User` entity from scratch the way v2 did for ASP.NET Core.
- **Breeze** (Laravel's lightweight starter kit) scaffolds login, and — the part to remove — registration. Delete/disable the register route and its Livewire component (or Blazor-equivalent Breeze stack); accounts are created only via an admin-only page or an `artisan` command (e.g. `php artisan make:command CreateUser`), matching the "generate them an account" model from v2 unchanged.
- **`is_admin` boolean column** on the `users` table — same reasoning as v2's `IsAdmin` flag: only distinction needed is "can create accounts" vs. not, no full role system until a real need appears.
- **Session/cookie config:** Laravel's default session cookie config already sets `Secure` and `HttpOnly` correctly when `APP_ENV=production` and the app is served over HTTPS — verify this explicitly in `config/session.php` rather than assuming the default, same caution as v2's manual cookie-flag requirement.
- HTTPS still handled by Caddy in front of the app container (unchanged from v2's deployment plan).

**Out of scope for v3, same as v2:** email verification, password-reset flows, external OAuth providers, multi-role permission systems beyond the single admin flag, audit logging beyond `last_login_at`.

## Deployment & Infrastructure Plan

**Hosting: DigitalOcean, self-managed Droplet running Docker**

- **Droplet: Basic, $12/mo tier (1 vCPU / 2GB RAM)** — enough headroom to run the app container, Postgres container, and a reverse proxy (Caddy or Traefik for automatic TLS) without the memory pressure the $6/mo tier would risk. The $24/mo tier is unnecessary for single-user traffic. Laravel's PHP-FPM footprint is comparably light to ASP.NET Core's here — this budget line doesn't change with the stack pivot.
- **Reverse proxy: Caddy** (simplest automatic HTTPS/cert renewal for a low-maintenance personal setup) in front of the app container.
- **Orchestration: `docker-compose.yml`** with services for the app, Postgres, and Caddy. No Kubernetes, no managed container platform — proportionate to a single-user, low-traffic tool.

**Backups**

- **Skip DO's droplet-level backups** (20–30% of droplet cost for a whole-VM snapshot) as the primary strategy — coarse, and redundant with an app-level approach for a tool where the *data* (decisions, prototypes, security notes), not the OS install, is the thing worth protecting.
- **Primary backup: scheduled `pg_dump`** (cron, e.g. nightly) pushed off-box to DO Spaces (or any S3-compatible bucket) — cheap (cents/month for the dump sizes involved here) and protects the actual asset.
- **Revisit droplet-level backups later** (~$2.40/mo at the $12 tier) only once there's meaningful data volume or the app proves daily use — not needed at launch.

**Budget**

| Item | Cost |
|---|---|
| Droplet (Basic, 2GB) | $12/mo |
| Off-box dump storage (Spaces or equivalent) | ~$1–2/mo |
| **Subtotal** | **~$13–14/mo** |
| Buffer / optional droplet backups later | up to $20/mo total |

$20/mo is a comfortable planning ceiling — it covers the baseline with room to add droplet backups or bump droplet size later without needing to revisit the budget.

**One operational note carried forward:** a powered-off droplet still bills at full rate on DO — there's no "pause" state, only destroy or snapshot-and-delete. Not relevant for an always-on personal tool, but worth remembering if this ever gets mothballed.

## What Changed vs. What Didn't

**Changed:**
- Client: MAUI desktop → web app (Blazor Server/SPA in v2, revised to Laravel + Livewire/Inertia in v3)
- Backend language/framework: ASP.NET Core (v2) → Laravel/PHP (v3), on tooling-friction grounds — see v3 changelog
- Local dev environment: hand-assembled `docker-compose.yml` (v2) → Laravel Sail for local dev, hand-written compose retained for production deployment (v3)
- Data layer pattern: explicit repository interfaces (v1/v2) → direct Eloquent usage recommended (v3) — the SQLite→Postgres portability reason for the interfaces already happened once and won't recur
- Auth implementation: hand-rolled `User`/`IUserRepository`/cookie auth (v2, for ASP.NET Core) → Laravel's built-in Breeze scaffolding with registration removed (v3) — same behavior and constraints, less to hand-build
- Database: local SQLite (v1) → self-hosted Postgres in Docker (v2, unchanged in v3)
- The "Forward-Looking Design Note: Remote Database Readiness" section from v1 is resolved as of v2 — the minimal-API-in-front-of-Postgres path it described is simply the architecture now, not a future phase. Unaffected by the v3 language pivot.

**Unchanged:**
- All five core modules and their scope boundaries
- The full data model shape (DecisionRecord, DecisionOption, DecisionLink, VettingItem, Prototype, FeedSource, RadarItem, SecurityNote, Tag/TagAssignment, ItemLink) — field-level structure carries over; implementation moves from EF Core entities/migrations to Eloquent models/Laravel migrations, same schema
- The "discard, not archive" triage principle for the Tech Radar module
- The decision to cut a standalone `Reference` entity in favor of `ExternalUrl` fields on `VettingItem` and `SecurityNote`
- Postgres, self-hosted in Docker, on a $12/mo DO droplet with Caddy + `pg_dump`-to-Spaces backups
- No self-service registration; accounts created only by an admin; `is_admin`-style single flag instead of a role system

## Data Model

Carried over from v1/v2 without structural changes — module-by-module entities are unaffected by the client/hosting/language pivots. **Implementation note for v3:** these translate to Laravel migrations + Eloquent models rather than EF Core entities. `enum` fields map to Laravel's native `enum` cast (PHP 8.1+ backed enums) or a plain string column with app-level validation, per your preference; `markdown` fields are plain `text`/`longtext` columns, same as they were plain `string`/`nvarchar` under EF Core — no schema change, just tooling.

### Decision Records Module

Modeled directly against real ADRs written for Vision Next Gen (VNG-ARCH-001/002/003, VNG-XREF-001), not a generic ADR template — the fields below reflect actual usage, including partial supersession and a dependency cross-reference pattern that gets modeled as data instead of a hand-maintained XREF document.

#### DecisionRecord

| Field | Type | Notes |
|---|---|---|
| `Id` | Guid/int PK | |
| `ProjectPrefix` | string | e.g. `VNG` |
| `Category` | string | e.g. `ARCH`, `LANG`, `INFRA` — free text, not enum, since categories get invented per project |
| `Sequence` | int | |
| `DocumentId` | computed | `{ProjectPrefix}-{Category}-{Sequence:000}` |
| `Title` | string | |
| `Status` | enum | `Draft`, `UnderRework`, `Decided`, `Superseded` |
| `Author` | string | |
| `Deciders` | string | free text; `N/A` is a valid value |
| `Affects` | string | free text |
| `ProposalContext` | markdown | the "Context" section |
| `Recommendation` | markdown | the "Decision/Recommendation" section |
| `Consequences` | markdown | optional — not every ADR uses this section |
| `ConditionsForRevisiting` | markdown | optional |
| `DateCreated` | DateTime | |
| `DateUpdated` | DateTime | |

#### DecisionOption (child of DecisionRecord)

Options considered are modeled as a child entity, not a text blob — matches actual ADR structure (Name + description + explicit Pros/Cons per option) and keeps it queryable later (e.g. "did I already weigh microservices against this team size somewhere").

| Field | Type | Notes |
|---|---|---|
| `Id` | Guid/int PK | |
| `DecisionRecordId` | FK | |
| `Name` | string | |
| `Description` | markdown | |
| `Pros` | markdown | |
| `Cons` | markdown | |
| `WasChosen` | bool | |

#### DecisionLink

Replaces a separate XREF document type. Cross-referencing between ADRs is rarely used, so it's modeled as relationship rows attached to the ADRs they connect rather than its own document — the app can render a record's links as a table underneath it, same shape as a hand-written XREF doc, generated from data instead.

| Field | Type | Notes |
|---|---|---|
| `Id` | Guid/int PK | |
| `SourceId` | FK → DecisionRecord | |
| `TargetId` | FK → DecisionRecord | |
| `RelationshipType` | enum | `Constrains`, `Supersedes`, `RelatedTo` |
| `ScopeNote` | string | e.g. "deployment model section only" — covers partial supersession, where the source record is deliberately left unedited as a historical snapshot rather than fully superseded |
| `RoleNote` | string | e.g. "Upstream, constrains language choice" |
| `ImpactSummary` | markdown | the narrative explaining why the relationship matters |

**Not modeled as an entity:** people/stakeholders — `Affects` and `Deciders` stay free-text rather than a `Person` entity, since this is single-user and there's no current need to query "everything that touched X person."

### Vetting Log Module

Greenfield design (no existing examples to model against, unlike Decision Records). Covers the intake-through-resolution lifecycle for proposals, before they become commitments.

#### VettingItem

| Field | Type | Notes |
|---|---|---|
| `Id` | Guid/int PK | |
| `Title` | string | short label for the proposal |
| `SourceType` | enum | `Meeting`, `Stakeholder`, `TechRadar`, `SelfInitiated` |
| `SourceDetail` | string | free text, e.g. who raised it / which meeting |
| `DateRaised` | DateTime | |
| `ProposalDescription` | markdown | what's actually being proposed |
| `Assessment` | markdown | feasibility/ramifications write-up |
| `Status` | enum | `New`, `InProgress`, `Vetted`, `NeedsPrototype`, `Rejected` |
| `RejectionReason` | markdown | nullable, populated only when `Status = Rejected` |
| `DateResolved` | DateTime | nullable while still open |
| `ExternalUrl` | string | optional, single one-off external citation (see Reference decision below) |

### Cross-Module Linking: ItemLink

A generic link table for cross-module traceability (radar flags something → vetting assesses it → prototype tests it → decision record closes it). Chosen over bespoke FKs per relationship (e.g. `RelatedPrototypeId` on VettingItem) because tracing the full chain across modules is the app's core reason to exist, not a side feature — one relationship mechanism to learn and query beats reinventing link tables for every module pair.

This is separate from `DecisionLink`, which stays specific to ADR-to-ADR relationships and carries richer fields (`RoleNote`, `ImpactSummary`) that don't apply to simpler cross-module links (e.g. "this prototype came from this radar item" doesn't need an impact narrative).

#### ItemLink

| Field | Type | Notes |
|---|---|---|
| `Id` | Guid/int PK | |
| `SourceType` | enum | `VettingItem`, `Prototype`, `RadarItem`, `SecurityNote`, `DecisionRecord` |
| `SourceId` | Guid/int | polymorphic — see integrity note below |
| `TargetType` | enum | same enum as `SourceType` |
| `TargetId` | Guid/int | polymorphic |
| `LinkType` | enum | `SpawnedFrom`, `ResultedIn`, `RelatedTo` |
| `Note` | string | optional, short free text |
| `DateLinked` | DateTime | |

**Integrity tradeoff:** polymorphic `SourceId`/`TargetId` means SQLite/EF can't enforce real foreign-key constraints (can't guarantee a given Guid actually exists in the table `SourceType` claims). Decision: accept this and validate at the app level when creating a link, rather than building a junction table per module pair for DB-enforced integrity. Reasonable given this is a single-user local app with no concurrent-write risk — noted here as a knowingly-accepted tradeoff, not an oversight.

> **v2 note:** this tradeoff was written against SQLite in v1; it applies identically under Postgres — Postgres can't enforce a polymorphic FK any more than SQLite could, so the same app-level validation approach carries over unchanged.

### Prototype / Spike Tracker Module

#### Prototype

| Field | Type | Notes |
|---|---|---|
| `Id` | Guid/int PK | |
| `Title` | string | short label |
| `Status` | enum | `Planned`, `InProgress`, `Completed`, `Abandoned` — `Abandoned` is its own state, not a subtype of a rejected result, since a prototype can die from priority/time rather than being disproven |
| `Hypothesis` | markdown | what's being tested and why |
| `TestApproach` | markdown | what was actually built/tried |
| `Result` | markdown | nullable while `Status` is `Planned`/`InProgress` |
| `AbandonedReason` | markdown | nullable, populated only when `Status = Abandoned` — distinct from `Result`, since "abandoned, no result yet" and "tested, here's the result" are different shapes of note |
| `ConfidenceLevel` | enum | `Low`, `Medium`, `High` — confidence in the result, not a numeric score |
| `IsReusable` | bool | nullable until resolved |
| `ReusabilityNote` | markdown | optional, populated when `IsReusable = true` |
| `RepoReference` | string | branch name or repo URL — direct pointer to the code (GitHub is system of record); kept as a plain field rather than an `ItemLink` since it's 1:1, not a conceptual chain |
| `DateStarted` | DateTime | |
| `DateCompleted` | DateTime | nullable |

### Tech Radar Module

Design principle from the spec ("discard, not archive") is bent slightly here for a practical reason: with zero persistence for untriaged items, there's no dedup and no way to avoid re-showing already-dismissed items on every fetch. Instead, everything fetched is stored with a triage status, and discarded items are soft-deleted (hidden, not removed) rather than kept as a growing archive — preserves the "no accumulating noise" intent while still allowing a too-hasty dismissal to be revisited.

#### FeedSource

| Field | Type | Notes |
|---|---|---|
| `Id` | Guid/int PK | |
| `Name` | string | display name |
| `Url` | string | feed URL |
| `FeedType` | enum | `RSS`, `Atom`, `Other` |
| `IsActive` | bool | lets you pause a noisy/dead source without deleting its history |
| `LastFetched` | DateTime | nullable |

#### RadarItem

| Field | Type | Notes |
|---|---|---|
| `Id` | Guid/int PK | |
| `FeedSourceId` | FK → FeedSource | nullable, if manually added rather than pulled from a feed |
| `Title` | string | from the feed |
| `Url` | string | link to the original item; also the dedup key on re-fetch |
| `PublishedDate` | DateTime | from the feed |
| `DateFetched` | DateTime | |
| `TriageStatus` | enum | `Pending`, `Relevant`, `Discarded` |
| `DateTriaged` | DateTime | nullable while `Pending` |
| `RelevanceNote` | markdown | nullable, populated only when `TriageStatus = Relevant` — this is what actually gets read later, not the raw feed summary |
| `IsHidden` | bool | soft-delete flag for discarded items — hidden from normal views, not removed |

Relevant items are the natural candidates for an `ItemLink` into `VettingItem` when something's worth acting on.

### Security Posture Module

Reflects the existing CRA compliance/security remediation function already running at JYGA: incoming findings get triaged (issue vs. non-issue) and, if real, routed to the appropriate lead — not a generic flat log.

#### SecurityNote

| Field | Type | Notes |
|---|---|---|
| `Id` | Guid/int PK | |
| `Title` | string | short label for the finding |
| `Source` | enum | `CRACompliance`, `CodeReview`, `AWSInfra`, `PersonalChecklist`, `ExternalReport`, `Other` |
| `Category` | string | free text, loosely from personal threat taxonomy (e.g. "SSRF," "IAM," "credential exposure") rather than a hard enum, since the taxonomy is self-defined and evolves |
| `Severity` | enum | `Low`, `Medium`, `High`, `Critical` |
| `Finding` | markdown | what was found |
| `IsIssue` | bool | triage call — actual issue vs. non-issue |
| `NonIssueReason` | markdown | nullable, populated only when `IsIssue = false` |
| `RoutedTo` | enum | `WebTeamLead`, `EmbeddedTeamLead`, `SelfHandled`, `Unrouted` |
| `Status` | enum | `Flagged`, `Routed`, `Remediated`, `Deferred`, `NonIssue` — `NonIssue` kept distinct from `Remediated` so "closed, nothing to fix" doesn't get conflated with actual remediation counts |
| `DeferralReason` | markdown | nullable, populated only when `Status = Deferred` |
| `DateFlagged` | DateTime | |
| `DateResolved` | DateTime | nullable |
| `ExternalUrl` | string | optional, single one-off external citation (see Reference decision below) |

### Shared / Cross-Cutting Entities

#### Tag

| Field | Type | Notes |
|---|---|---|
| `Id` | Guid/int PK | |
| `Name` | string | e.g. "CRA," "VNG," "embedded," "AWS" |
| `Color` | string | optional, hex code — visual scanning aid, not required |

#### TagAssignment (junction)

| Field | Type | Notes |
|---|---|---|
| `Id` | Guid/int PK | |
| `TagId` | FK → Tag | |
| `ModuleType` | enum | `DecisionRecord`, `VettingItem`, `Prototype`, `RadarItem`, `SecurityNote` |
| `ModuleId` | Guid/int | polymorphic — same accepted tradeoff as `ItemLink` (app-level validation, no DB-enforced FK) |

#### Reference — cut, not modeled as its own entity

Considered a standalone `Reference` entity for external links, but everything that needed structured referencing is already covered: `Prototype.RepoReference` (code pointer), `DecisionLink` (ADR-to-ADR), `ItemLink` (cross-module), `RadarItem.Url` (source link inherent to the item). What's left over — an occasional one-off citation (a CVE advisory, a Slack thread) — is rare enough that a full entity + join table is more machinery than justified.

**Decision:** add a single optional `ExternalUrl` (string) field directly on `VettingItem` and `SecurityNote`, the two modules most likely to cite an outside source. Promote to a real entity later only if that field turns out to be used for more than one URL regularly.

## Open Questions / Next Steps (v3)

- [ ] Repository interfaces vs. direct Eloquent — leaning toward dropping the explicit interface layer per the reasoning above; revisit only if a concrete future need for swappable data access resurfaces
- [ ] Confirm production `docker-compose.yml` service layout (app / Postgres / Caddy) — separate from Sail's dev-oriented compose file — and volume strategy for Postgres data persistence
- [ ] Write the `pg_dump` cron script and off-box push target before real data accumulates
- [ ] Pick a markdown editor component for React (CodeMirror-based, e.g. `@uiw/react-md-editor` or similar) — no longer blocked on a Livewire-vs-Inertia decision
- [ ] MVP scope cap: which single field/status/source per module to start with — still an open question carried from v1, unaffected by any of the pivots since
- [ ] Set up Breeze (React/Inertia stack) with registration disabled, `is_admin` column, and an admin-only account-creation command/page before the droplet is exposed to the internet — this is a launch blocker, not a nice-to-have
