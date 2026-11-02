import { Fragment, useState } from "react";
import { Link } from "react-router-dom";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import { FaAngleDown, FaAngleRight, FaPen, FaTrash } from "react-icons/fa";
import { MdMergeType } from "react-icons/md";
import { formatId, useGlobalStore } from "../utils";
import { useScrollFade } from "#src/utils/useScrollFade.ts";
import { SkeletonTable } from "./Skeleton";
import { StyledButton } from "./StyledButton";
import { StyledLabeledInput } from "./StyledLabeledInput";
import { ConfirmDialog } from "./ConfirmDialog";
import { MAX_AUDIT_TAG_LENGTH, normalizeAuditTagName } from "./AuditTagInput";
import styles from "./AuditTagsAdmin.module.scss";

const apiClient = API.generateClient();

interface TagAudit {
  id: string;
  name: string;
  interval: string;
  user: { name: string; email: string } | null;
  scans: { blockers_aggregate: { aggregate: { count: number } } }[];
}

interface AdminTag {
  id: string;
  name: string;
  created_at: string;
  audits: TagAudit[];
  blockers: number;
}

type TagAction = { type: "rename" | "merge" | "delete"; tag: AdminTag } | null;

const latestBlockers = (audit: TagAudit) =>
  audit.scans[0]?.blockers_aggregate?.aggregate?.count ?? 0;

// Admin-only (Account > Tags): every audit tag, the audits using it, and
// rename / merge / delete
export const AuditTagsAdmin = () => {
  const queryClient = useQueryClient();
  const { setAnnounceMessage } = useGlobalStore();
  const [scrollFadeRef, showScrollFade] = useScrollFade();
  const [searchString, setSearchString] = useState("");
  const [expanded, setExpanded] = useState<string[]>([]);
  const [action, setAction] = useState<TagAction>(null);
  const [renameValue, setRenameValue] = useState("");
  const [mergeTargetId, setMergeTargetId] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { data: tags, isLoading, error } = useQuery({
    queryKey: ["auditTags", "admin"],
    queryFn: async () => {
      const response = (await apiClient.graphql({
        query: `{
          audit_tags(order_by: {name: asc}) {
            id
            name
            created_at
            assignments(order_by: {audit: {name: asc}}) {
              audit {
                id
                name
                interval
                user { name email }
                scans(order_by: {created_at: desc}, limit: 1) {
                  blockers_aggregate { aggregate { count } }
                }
              }
            }
          }
        }`,
      })) as any;
      return (response.data.audit_tags as any[]).map((tag): AdminTag => {
        const audits: TagAudit[] = tag.assignments.map((a: any) => a.audit).filter(Boolean);
        return {
          id: tag.id,
          name: tag.name,
          created_at: tag.created_at,
          audits,
          blockers: audits.reduce((sum, audit) => sum + latestBlockers(audit), 0),
        };
      });
    },
  });

  const filteredTags = (tags ?? []).filter((tag) =>
    tag.name.toLowerCase().includes(searchString.trim().toLowerCase())
  );
  const taggedAuditCount = new Set((tags ?? []).flatMap((tag) => tag.audits.map((audit) => audit.id))).size;

  const toggleExpanded = (id: string) =>
    setExpanded((current) => current.includes(id) ? current.filter((x) => x !== id) : [...current, id]);

  const openAction = (type: "rename" | "merge" | "delete", tag: AdminTag) => {
    setRenameValue(tag.name);
    setMergeTargetId("");
    setAction({ type, tag });
  };

  const runAction = async (path: string, body: object, successMessage: string) => {
    setIsSubmitting(true);
    try {
      const response = (await (
        await API.post({ apiName: "auth", path, options: { body: body as any } }).response
      ).body.json()) as { status: string; message?: string };
      if (response?.status !== "success") {
        setAnnounceMessage(response?.message ?? "Something went wrong.", "error");
        return;
      }
      await queryClient.invalidateQueries({ queryKey: ["auditTags"] });
      queryClient.invalidateQueries({ queryKey: ["audits"] });
      queryClient.invalidateQueries({ queryKey: ["audit"] });
      setAnnounceMessage(successMessage, "success");
      setAction(null);
    } catch (err) {
      console.error(`${path} failed:`, err);
      setAnnounceMessage("Something went wrong.", "error");
    } finally {
      setIsSubmitting(false);
    }
  };

  const normalizedRename = normalizeAuditTagName(renameValue);
  const mergeTarget = tags?.find((tag) => tag.id === mergeTargetId);
  const auditCountLabel = (count: number) => `${count} audit${count === 1 ? "" : "s"}`;

  if (error) {
    return <div>Error loading tags: {String(error)}</div>;
  }

  return (
    <div className={styles.AuditTagsAdmin}>
      <div className={styles.header}>
        <div>
          <h2>Audit Tags</h2>
          {tags && (
            <p className="font-small">
              {tags.length} tag{tags.length === 1 ? "" : "s"} across {auditCountLabel(taggedAuditCount)}.
            </p>
          )}
        </div>
        <StyledLabeledInput>
          <label htmlFor="auditTagsAdminSearch">Search Tags</label>
          <input
            id="auditTagsAdminSearch"
            type="text"
            value={searchString}
            onChange={(e) => setSearchString(e.target.value)}
          />
        </StyledLabeledInput>
      </div>

      {isLoading ? (
        <SkeletonTable columns={5} rows={5} headers={["Tag", "Audits", "Blockers", "Created", "Actions"]} />
      ) : (
        <div className="table-container">
          <div className={"table-scroll-wrapper" + (showScrollFade ? " scroll-fade-active" : "")} ref={scrollFadeRef} tabIndex={0} role="region" aria-label="Audit tags table, scrollable horizontally">
            <table aria-label="Audit tags">
              <thead>
                <tr>
                  <th scope="col">Tag</th>
                  <th scope="col">Audits</th>
                  <th scope="col">Blockers (latest scans)</th>
                  <th scope="col">Created</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
                {filteredTags.length === 0 ? (
                  <tr>
                    <td colSpan={5}>{tags?.length ? "No tags match your search" : "No tags have been created yet"}</td>
                  </tr>
                ) : (
                  filteredTags.map((tag) => {
                    const isExpanded = expanded.includes(tag.id);
                    const panelId = `tag-audits-${tag.id}`;
                    return (
                      <Fragment key={tag.id}>
                        <tr>
                          <td>
                            <button
                              className={styles["expand-button"]}
                              aria-expanded={isExpanded}
                              aria-controls={panelId}
                              onClick={() => toggleExpanded(tag.id)}
                            >
                              {isExpanded ? <FaAngleDown aria-hidden="true" /> : <FaAngleRight aria-hidden="true" />}
                              <span>{tag.name}</span>
                            </button>
                          </td>
                          <td>{tag.audits.length}</td>
                          <td>{tag.blockers}</td>
                          <td>{new Date(tag.created_at).toLocaleDateString()}</td>
                          <td>
                            <div className={styles.actions}>
                              <StyledButton
                                label={`Rename tag ${tag.name}`}
                                icon={<FaPen />}
                                showLabel={false}
                                onClick={() => openAction("rename", tag)}
                              />
                              <StyledButton
                                label={`Merge tag ${tag.name} into another tag`}
                                icon={<MdMergeType />}
                                showLabel={false}
                                onClick={() => openAction("merge", tag)}
                                disabled={(tags?.length ?? 0) < 2}
                              />
                              <StyledButton
                                label={`Delete tag ${tag.name}`}
                                icon={<FaTrash />}
                                showLabel={false}
                                onClick={() => openAction("delete", tag)}
                              />
                            </div>
                          </td>
                        </tr>
                        {isExpanded && (
                          <tr id={panelId} className={styles["audits-row"]}>
                            <td colSpan={5}>
                              {tag.audits.length === 0 ? (
                                <p className="font-small">No audits use this tag.</p>
                              ) : (
                                <ul aria-label={`Audits tagged ${tag.name}`}>
                                  {tag.audits.map((audit) => (
                                    <li key={audit.id}>
                                      <Link to={`/audits/${formatId(audit.id)}`}>{audit.name}</Link>
                                      <span className="font-small">
                                        {audit.user?.name ?? "Unknown user"} · Runs {audit.interval} · {latestBlockers(audit)} blockers
                                      </span>
                                    </li>
                                  ))}
                                </ul>
                              )}
                            </td>
                          </tr>
                        )}
                      </Fragment>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      <ConfirmDialog
        open={action?.type === "rename"}
        onOpenChange={(open) => { if (!open) setAction(null); }}
        title="Rename tag"
        description={<>Rename <strong>"{action?.tag.name}"</strong>. The new name will show on all {auditCountLabel(action?.tag.audits.length ?? 0)} using it.</>}
        confirmLabel="Rename"
        loading={isSubmitting}
        confirmDisabled={!normalizedRename || normalizedRename === action?.tag.name}
        onConfirm={() => runAction(
          "/renameAuditTag",
          { id: action!.tag.id, name: normalizedRename },
          `Renamed tag "${action!.tag.name}" to "${normalizedRename}".`
        )}
      >
        <StyledLabeledInput>
          <label htmlFor="renameAuditTag">New name:</label>
          <input
            id="renameAuditTag"
            type="text"
            value={renameValue}
            maxLength={MAX_AUDIT_TAG_LENGTH}
            onChange={(e) => setRenameValue(e.target.value)}
          />
        </StyledLabeledInput>
      </ConfirmDialog>

      <ConfirmDialog
        open={action?.type === "merge"}
        onOpenChange={(open) => { if (!open) setAction(null); }}
        title="Merge tag"
        description={
          mergeTarget
            ? <>All {auditCountLabel(action?.tag.audits.length ?? 0)} tagged <strong>"{action?.tag.name}"</strong> will be tagged <strong>"{mergeTarget.name}"</strong>, and <strong>"{action?.tag.name}"</strong> will be deleted.</>
            : <>Choose a tag to merge <strong>"{action?.tag.name}"</strong> into. The merged tag will be deleted.</>
        }
        confirmLabel="Merge"
        loading={isSubmitting}
        confirmDisabled={!mergeTarget}
        onConfirm={() => runAction(
          "/mergeAuditTags",
          { sourceId: action!.tag.id, targetId: mergeTargetId },
          `Merged tag "${action!.tag.name}" into "${mergeTarget!.name}".`
        )}
      >
        <StyledLabeledInput>
          <label htmlFor="mergeAuditTagTarget">Merge into:</label>
          <select
            id="mergeAuditTagTarget"
            value={mergeTargetId}
            onChange={(e) => setMergeTargetId(e.target.value)}
          >
            <option value="">Choose a tag...</option>
            {tags?.filter((tag) => tag.id !== action?.tag.id).map((tag) => (
              <option key={tag.id} value={tag.id}>{tag.name} ({auditCountLabel(tag.audits.length)})</option>
            ))}
          </select>
        </StyledLabeledInput>
      </ConfirmDialog>

      <ConfirmDialog
        open={action?.type === "delete"}
        onOpenChange={(open) => { if (!open) setAction(null); }}
        title="Delete tag"
        description={<>Delete <strong>"{action?.tag.name}"</strong>? It will be removed from {auditCountLabel(action?.tag.audits.length ?? 0)}. The audits themselves won't be affected. This can't be undone.</>}
        confirmLabel="Delete Tag"
        confirmVariant="red"
        loading={isSubmitting}
        onConfirm={() => runAction(
          "/deleteAuditTag",
          { id: action!.tag.id },
          `Deleted tag "${action!.tag.name}".`
        )}
      />
    </div>
  );
};
