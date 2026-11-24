import { useRef, useState } from "react";
import Select, { MultiValue, SelectInstance } from "react-select";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import { FaPlus } from "react-icons/fa";
import { StyledButton } from "./StyledButton";
import { useGlobalStore } from "../utils";
import { useSelectStyles } from "../utils/useSelectStyles";
import styles from "./TagReportAddAudits.module.scss";

const apiClient = API.generateClient();

interface AuditOption {
  value: string;
  label: string;
}

interface TagReportAddAuditsProps {
  tagId: string;
  tagName: string;
  /** Audits that already have the tag (left out of the picker) */
  taggedAuditIds: string[];
}

// IDs may arrive with or without hyphens (route params use formatId)
const normalizeId = (id: string) => id.replace(/-/g, "").toLowerCase();

// "Add audits to this tag" picker on the Tag Report
export const TagReportAddAudits = ({ tagId, tagName, taggedAuditIds }: TagReportAddAuditsProps) => {
  const queryClient = useQueryClient();
  const { setAnnounceMessage } = useGlobalStore();
  const selectStyles = useSelectStyles();
  const selectRef = useRef<SelectInstance<AuditOption, true>>(null);
  const [selected, setSelected] = useState<AuditOption[]>([]);
  const [isAdding, setIsAdding] = useState(false);

  // Names only; the ["audits"] prefix means any audit change refreshes it
  const { data: allAudits, isLoading } = useQuery({
    queryKey: ["audits", "names"],
    queryFn: async () =>
      ((await apiClient.graphql({
        query: `{audits(order_by: {name: asc}, where: {interval: {_neq: "Quick Scan"}}) {id name}}`,
      })) as any)?.data?.audits as { id: string; name: string }[],
  });

  const tagged = new Set(taggedAuditIds.map(normalizeId));
  const options: AuditOption[] = (allAudits ?? [])
    .filter((audit) => !tagged.has(normalizeId(audit.id)))
    .map((audit) => ({ value: audit.id, label: audit.name }));

  const addAudits = async () => {
    if (!selected.length) return;
    setIsAdding(true);
    try {
      const response = (await (
        await API.post({
          apiName: "auth",
          path: "/updateTagAudits",
          options: { body: { tagId, add: selected.map((option) => option.value) } },
        }).response
      ).body.json()) as unknown as { status: string; message?: string; added?: string[] };
      if (response?.status !== "success") {
        setAnnounceMessage(response?.message ?? "Unable to add audits.", "error");
        return;
      }
      const count = response.added?.length ?? 0;
      await queryClient.invalidateQueries({ queryKey: ["auditTags"] });
      queryClient.invalidateQueries({ queryKey: ["audits"] });
      queryClient.invalidateQueries({ queryKey: ["audit"] });
      setSelected([]);
      setAnnounceMessage(
        count === 1
          ? `Added ${selected[0].label} to ${tagName}.`
          : `Added ${count} audits to ${tagName}.`,
        "success"
      );
      selectRef.current?.focus();
    } catch (err) {
      console.error("Failed to add audits to tag:", err);
      setAnnounceMessage("Unable to add audits.", "error");
    } finally {
      setIsAdding(false);
    }
  };

  return (
    <div className={styles.TagReportAddAudits}>
      <div className={styles.field}>
        <label htmlFor="tagReportAddAudits">Add audits to this tag:</label>
        <Select<AuditOption, true>
          ref={selectRef}
          inputId="tagReportAddAudits"
          className="react-select"
          isMulti
          isClearable={false}
          isLoading={isLoading}
          isDisabled={isAdding}
          options={options}
          value={selected}
          onChange={(value: MultiValue<AuditOption>) => setSelected([...value])}
          placeholder="Search audits..."
          noOptionsMessage={() => (allAudits && !options.length ? "Every audit already has this tag" : "No matching audits")}
          styles={selectStyles}
        />
      </div>
      <StyledButton
        onClick={addAudits}
        label={selected.length > 1 ? `Add ${selected.length} Audits` : "Add Audit"}
        icon={<FaPlus />}
        variant="dark"
        loading={isAdding}
        loadingText="Adding..."
        disabled={!selected.length || isAdding}
      />
    </div>
  );
};
