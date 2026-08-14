// Tolerates however this actually got set: this repo's own Terraform always
// writes the literal string "0"/"1" (see infrastructure/main.tf), but the
// pre-existing production/staging Lambda isn't Terraform-managed — its
// SSO_ENABLED value was set by hand outside this repo at some point, almost
// certainly as "true" rather than "1". Excluding the known-false spellings
// is robust to either convention; requiring one specific known-true spelling
// (e.g. `=== '1'`) is not — that broke SSO on the real staging Lambda even
// though nothing about SSO itself changed, only how strictly this got parsed.
const FALSY_VALUES = ['', '0', 'false'];

export const isSsoEnabled = !FALSY_VALUES.includes((process.env.SSO_ENABLED ?? '').toLowerCase());
