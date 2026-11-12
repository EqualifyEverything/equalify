import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import { useGlobalStore } from "../utils";

const apiClient = API.generateClient();

/**
 * Blocker ids currently ignored in this audit, mapped to their optional
 * status (why it's ignored; null when none is set). `.has(id)` = ignored.
 * The status is a property of the hash (as in Recommendations and past
 * scans), so a row without one takes it from a sibling row of the same hash.
 */
export const useIgnoredBlockers = (auditId: string) =>
  useQuery({
    queryKey: ["ignoredBlockers", auditId],
    queryFn: async () => {
      const response = (await apiClient.graphql({
        query: `query ($audit_id: uuid!) {
          ignored_blockers(where: {audit_id: {_eq: $audit_id}}) {
            blocker_id
            content_hash_id
            status
          }
        }`,
        variables: { audit_id: auditId },
      })) as any;
      const rows: any[] = response.data.ignored_blockers;
      const statusByHash = new Map<string, string>();
      for (const ib of rows) {
        if (ib.status && ib.content_hash_id) statusByHash.set(ib.content_hash_id, ib.status);
      }
      return new Map<string, string | null>(
        rows.map((ib) => [ib.blocker_id, ib.status ?? statusByHash.get(ib.content_hash_id) ?? null])
      );
    },
  });

/**
 * Toggle ignore status for a blocker — hash-wide in both directions, so
 * ignoring one occurrence ignores every identical node in the latest scan
 * (and carries forward to future scans via content_hash_id), and un-ignoring
 * clears all of them. Shared by the Detailed table and Recommendations view.
 */
export const useToggleIgnore = (auditId: string) => {
  const queryClient = useQueryClient();
  const { setAnnounceMessage } = useGlobalStore();

  return useMutation({
    mutationFn: async ({
      blockerId,
      contentHashId,
      isCurrentlyIgnored,
    }: {
      blockerId: string;
      contentHashId: string;
      isCurrentlyIgnored: boolean;
    }) => {
      if (isCurrentlyIgnored) {
        // Delete every row for this content hash, not just this blocker's row —
        // sibling rows from earlier scans share the hash and would re-ignore the
        // node on the next scan. blocker_id catches legacy rows with a NULL hash.
        await apiClient.graphql({
          query: `mutation ($audit_id: uuid!, $blocker_id: uuid!, $content_hash_id: uuid!) {
            delete_ignored_blockers(where: {
              audit_id: {_eq: $audit_id},
              _or: [
                {content_hash_id: {_eq: $content_hash_id}},
                {blocker_id: {_eq: $blocker_id}}
              ]
            }) {
              affected_rows
            }
          }`,
          variables: { audit_id: auditId, blocker_id: blockerId, content_hash_id: contentHashId },
        });
      } else {
        // Ignore every occurrence of this exact node in the latest scan.
        // Scoped to the latest scan so historical scans' rows (pre-migration)
        // don't balloon the payload — carry-forward covers them regardless.
        const idsResponse = (await apiClient.graphql({
          query: `query ($audit_id: uuid!, $content_hash_id: uuid!) {
            audits_by_pk(id: $audit_id) {
              scans(order_by: {created_at: desc}, limit: 1) {
                blockers(where: {content_hash_id: {_eq: $content_hash_id}}) {
                  id
                }
              }
            }
          }`,
          variables: { audit_id: auditId, content_hash_id: contentHashId },
        })) as any;
        const latestScanBlockers = idsResponse.data?.audits_by_pk?.scans?.[0]?.blockers;
        const blockerIds: string[] = latestScanBlockers?.length
          ? latestScanBlockers.map((b: any) => b.id)
          : [blockerId];
        await apiClient.graphql({
          query: `mutation ($objects: [ignored_blockers_insert_input!]!) {
            insert_ignored_blockers(objects: $objects) {
              affected_rows
            }
          }`,
          variables: {
            objects: blockerIds.map((id) => ({
              audit_id: auditId,
              blocker_id: id,
              content_hash_id: contentHashId,
            })),
          },
        });
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["ignoredBlockers", auditId] });
      queryClient.invalidateQueries({ queryKey: ["auditRecommendations", auditId] });
    },
    onError: (err) => {
      console.error("Failed to toggle ignore status:", err);
      setAnnounceMessage("Failed to update blocker status", "error");
      // Resync in case a partial change landed
      queryClient.invalidateQueries({ queryKey: ["ignoredBlockers", auditId] });
      queryClient.invalidateQueries({ queryKey: ["auditRecommendations", auditId] });
    },
  });
};

/**
 * Set (or clear, with null) the status on an ignored blocker. Hash-wide like
 * the ignore itself, so every copy of the node shows the same status and the
 * scan webhook carries it forward to future scans.
 */
export const useSetIgnoreStatus = (auditId: string) => {
  const queryClient = useQueryClient();
  const { setAnnounceMessage } = useGlobalStore();

  return useMutation({
    mutationFn: async ({
      blockerId,
      contentHashId,
      status,
    }: {
      blockerId: string;
      contentHashId: string;
      status: string | null;
    }) => {
      const response = (await apiClient.graphql({
        query: `mutation ($audit_id: uuid!, $blocker_id: uuid!, $content_hash_id: uuid!, $status: String) {
          update_ignored_blockers(
            where: {
              audit_id: {_eq: $audit_id},
              _or: [
                {content_hash_id: {_eq: $content_hash_id}},
                {blocker_id: {_eq: $blocker_id}}
              ]
            },
            _set: {status: $status}
          ) {
            affected_rows
          }
        }`,
        variables: { audit_id: auditId, blocker_id: blockerId, content_hash_id: contentHashId, status },
      })) as any;
      // Nothing matched: the blocker isn't ignored (yet, or any more), so the
      // status was not saved. Fail loudly instead of reporting success.
      if (!response?.data?.update_ignored_blockers?.affected_rows) {
        throw new Error("No ignored blocker found to set a status on");
      }
    },
    // Show the choice straight away in the Detailed table; the refetch on
    // settle brings in the copies on other rows (or the old value on failure).
    onMutate: async ({ blockerId, status }) => {
      // An ignore-list refetch already in flight would land after this and
      // put the old value back
      await queryClient.cancelQueries({ queryKey: ["ignoredBlockers", auditId] });
      queryClient.setQueryData<Map<string, string | null>>(["ignoredBlockers", auditId], (current) =>
        current?.has(blockerId) ? new Map(current).set(blockerId, status) : current
      );
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["ignoredBlockers", auditId] });
      queryClient.invalidateQueries({ queryKey: ["auditRecommendations", auditId] });
    },
    onError: (err) => {
      console.error("Failed to set ignore status:", err);
      setAnnounceMessage("Failed to update blocker status", "error");
      queryClient.invalidateQueries({ queryKey: ["ignoredBlockers", auditId] });
      queryClient.invalidateQueries({ queryKey: ["auditRecommendations", auditId] });
    },
  });
};
