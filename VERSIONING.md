# Versioning

Equalify ships as **one product from one repository**. Rather than version each
workspace independently, we keep a **single product version** that the whole
repo shares. This document is the spec for how that version is defined, bumped,
tagged, and surfaced.

## 1. Scheme

We follow [Semantic Versioning 2.0.0](https://semver.org): `MAJOR.MINOR.PATCH`.

Equalify is a deployed SaaS product, not a published library, so we interpret
the three numbers in terms of *our consumers* (API integrators, end users, and
the database/auth contract) rather than a public package API:

| Bump      | When                                                                                   | Examples |
|-----------|----------------------------------------------------------------------------------------|----------|
| **MAJOR** | Breaking change to a consumer contract: API request/response shape, auth, a DB migration that isn't backward compatible, or a removed feature. | New required auth header; renamed GraphQL field; non-additive schema migration. |
| **MINOR** | Backward-compatible new capability.                                                    | New scan type; new dashboard chart; additive API field; additive migration. |
| **PATCH** | Backward-compatible bug fix or internal change with no consumer-visible surface change. | Fix stuck-scan clearing; perf tuning; styling; refactors; chores. |

While we are pre-1.0 in spirit (the product is still moving fast), the **root
`package.json` already declares `1.0.0`**, so we treat `1.0.0` as the baseline
and move forward from there. If we want to signal "still unstable, breaking
changes may land in minor," that is a conscious decision to make once — see
§7. Until then, the table above is the rule.

### Pre-release versions

Staging builds and release candidates use a pre-release suffix:

- `vX.Y.Z-rc.N` — release candidate being validated on staging before promotion.

Pre-releases sort *below* their final version (`1.4.0-rc.1` < `1.4.0`), which is
exactly what we want.

## 2. Source of truth

- **The version lives in the root [`package.json`](package.json) `version` field.**
  This is the one canonical number.
- Workspace `package.json` versions (`apps/frontend`, `apps/backend`,
  `services/*`, `shared/types`) are **not** maintained independently. Set them
  to `"0.0.0"` and treat them as private/unpublished, or keep them in sync with
  the root via the release script (§4). Do not hand-edit them.
- The git tag (§3) is the immutable record of what a version *was*.

## 3. Git tags

- Format: **`vMAJOR.MINOR.PATCH`** (e.g. `v1.4.0`), with optional `-rc.N`.
- Annotated tags only (`git tag -a`), so they carry a message and date.
- One tag per release, created on the commit that is promoted to `main`.
- **Legacy tags** (`MVP-1`, `vMVP-5.1`, `v1-rc4`, …) are left in place for
  history but are abandoned. The first tag under this spec starts the new line
  cleanly — see §7 for the suggested starting point.

## 4. Release flow

This maps onto the existing trunk-based deploy model
([`.github/workflows/deploy-apps.yml`](.github/workflows/deploy-apps.yml)):
`staging` deploys to the staging env, `main` deploys to production.

```
feature branch ──PR──▶ staging ──(validate on staging env)──▶ main
                          │                                      │
                     -rc.N tag                              vX.Y.Z tag
                     (optional)                             + CHANGELOG entry
```

A release is cut **when promoting `staging` → `main`**:

1. Decide the bump (MAJOR/MINOR/PATCH) from the commits since the last tag
   (§5 makes this mechanical).
2. Run the release script (§6) on the merge commit. It:
   - updates `version` in root `package.json`,
   - updates the `CHANGELOG.md` (§5),
   - commits as `chore(release): vX.Y.Z`,
   - creates the annotated `vX.Y.Z` tag.
3. Push the tag. Existing deploy workflows fire from the branch push as they do
   today; the tag is the record, not a new deploy trigger (unless we wire that
   up later — §7).

## 5. Commit conventions → CHANGELOG

We already write [Conventional Commits](https://www.conventionalcommits.org)-style
subjects. Standardize on these types so bumps and changelogs can be derived:

| Type        | Bump  | Changelog section |
|-------------|-------|-------------------|
| `feat`      | MINOR | Added / Changed   |
| `fix`       | PATCH | Fixed             |
| `perf`      | PATCH | Changed           |
| `refactor`  | PATCH | (omit or Changed) |
| `chore`     | none  | (omit)            |
| `docs`      | none  | (omit)            |

A `!` after the type/scope or a `BREAKING CHANGE:` footer forces a **MAJOR**
bump regardless of type, e.g. `feat(api)!: require auth header`.

> **Normalize `bug` → `fix`.** Several existing commits use `bug(scope): …`.
> Conventional Commits has no `bug` type; it is treated as `fix`. Prefer `fix`
> going forward so tooling counts it correctly.

`CHANGELOG.md` follows [Keep a Changelog](https://keepachangelog.com): newest
version on top, grouped by Added / Changed / Fixed, each entry linked to its
compare range (`vA.B.C...vX.Y.Z`).

## 6. Tooling (basic, manual)

Start with **npm's built-in `version`** plus a thin script — no new heavy deps.

```jsonc
// root package.json
"scripts": {
  // bumps version, but DON'T let npm tag yet (we tag after changelog)
  "release:patch": "npm version patch --no-git-tag-version",
  "release:minor": "npm version minor --no-git-tag-version",
  "release:major": "npm version major --no-git-tag-version"
}
```

Manual flow per release:

```bash
npm run release:minor                       # bumps root package.json
# edit CHANGELOG.md (or generate — see upgrade path)
git commit -am "chore(release): v$(node -p "require('./package.json').version")"
git tag -a "v$(node -p "require('./package.json').version")" -m "Release notes…"
git push --follow-tags
```

## 7. Surfacing the version (so you can see what's deployed)

A version is only useful if you can read it off a running environment.

- **Frontend** — inject at build via Vite `define`, read from
  `package.json`, and show it (footer + `console.info`):

  ```ts
  // vite.config — define: { __APP_VERSION__: JSON.stringify(process.env.npm_package_version) }
  ```

- **Backend API** — expose a `GET /health` (or response header
  `X-Equalify-Version`) returning `{ version, commit, env }`. The deploy
  workflow can pass the version/short SHA in as an env var at build time.

- **Lambdas** — optional; tag the deployed function with the version or log it
  on cold start for traceability.

## 8. Suggested starting point

1. Reset the new line at **`v1.0.0`** (or `v1.1.0` if you want the first release
   under this spec to clearly differ from the legacy `1.0.0` in `package.json`).
2. Add `CHANGELOG.md` with an `## [Unreleased]` section.
3. Add the `release:*` scripts above.
4. Normalize commit types (`bug` → `fix`).
5. Add version surfacing (§7) when convenient.

## 9. Upgrade path (when manual gets old)

When cutting releases by hand becomes friction, adopt **[semantic-release](https://semantic-release.gitbook.io)**
or **[changesets](https://github.com/changesets/changesets)**:

- *semantic-release* — fully automated: on merge to `main` it reads commit
  types, computes the next version, updates the changelog, tags, and (optionally)
  triggers deploy. Best fit for our single-version, trunk-based model. Requires
  disciplined commit messages — which the conventions in §5 already give us.
- *changesets* — better if we ever move to **per-package independent
  versioning** (publishing `shared/types`, etc.). More ceremony than we need
  today.

Recommendation: stay manual (§6) until release cadence is regular, then move to
semantic-release without changing this spec's scheme.
```
