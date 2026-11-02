import { useRef, useState } from "react";
import CreatableSelect from "react-select/creatable";
import type { MultiValue, SelectInstance } from "react-select";
import { useQueryClient } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import { AuditTag, useAuditTags } from "../queries";
import { useGlobalStore } from "../utils";
import { useSelectStyles } from "../utils/useSelectStyles";
import { ConfirmDialog } from "./ConfirmDialog";

// Keep in sync with MAX_AUDIT_TAG_LENGTH in apps/backend/utils/auditTags.ts
export const MAX_AUDIT_TAG_LENGTH = 50;
export const normalizeAuditTagName = (name: string) => name.trim().replace(/\s+/g, " ");

interface TagOption {
  value: string;
  label: string;
}

interface AuditTagInputProps {
  inputId: string;
  value: AuditTag[];
  onChange: (tags: AuditTag[]) => void;
  label?: string;
  disabled?: boolean;
}

const toOption = (tag: AuditTag): TagOption => ({ value: tag.id, label: tag.name });

// Multi-select for an audit's tags, with lookahead over existing tags. Typing
// a name that doesn't exist offers to create it, behind a confirmation dialog.
export const AuditTagInput = ({
  inputId,
  value,
  onChange,
  label = "Tags",
  disabled = false,
}: AuditTagInputProps) => {
  const queryClient = useQueryClient();
  const { setAnnounceMessage } = useGlobalStore();
  const selectStyles = useSelectStyles();
  const { data: allTags, isLoading } = useAuditTags();
  const selectRef = useRef<SelectInstance<TagOption, true>>(null);
  const [pendingName, setPendingName] = useState<string | null>(null);
  const [isCreating, setIsCreating] = useState(false);

  const options = (allTags ?? []).map(toOption);

  const handleChange = (selected: MultiValue<TagOption>) => {
    onChange(selected.map((option) => ({ id: option.value, name: option.label })));
  };

  const isValidNewOption = (inputValue: string) => {
    const name = normalizeAuditTagName(inputValue).toLowerCase();
    return (
      name.length > 0 &&
      name.length <= MAX_AUDIT_TAG_LENGTH &&
      !options.some((option) => option.label.toLowerCase() === name)
    );
  };

  const createTag = async () => {
    if (!pendingName) return;
    setIsCreating(true);
    try {
      const response = (await (
        await API.post({
          apiName: "auth",
          path: "/createAuditTag",
          options: { body: { name: pendingName } },
        }).response
      ).body.json()) as unknown as { status: string; message?: string; created?: boolean; tag?: AuditTag };
      if (response?.status !== "success" || !response.tag) {
        setAnnounceMessage(response?.message ?? "Unable to create tag.", "error");
        return;
      }
      const tag = response.tag;
      await queryClient.invalidateQueries({ queryKey: ["auditTags"] });
      if (!value.some((existing) => existing.id === tag.id)) {
        onChange([...value, tag]);
      }
      setAnnounceMessage(
        response.created ? `Created tag "${tag.name}".` : `Added existing tag "${tag.name}".`,
        "success"
      );
      setPendingName(null);
    } catch (err) {
      console.error("Failed to create tag:", err);
      setAnnounceMessage("Unable to create tag.", "error");
    } finally {
      setIsCreating(false);
    }
  };

  return (
    <>
      <label htmlFor={inputId}>{label}:</label>
      <CreatableSelect<TagOption, true>
        ref={selectRef}
        inputId={inputId}
        className="react-select audit-tag-select"
        isMulti
        isClearable={false}
        isDisabled={disabled}
        isLoading={isLoading}
        options={options}
        value={value.map(toOption)}
        onChange={handleChange}
        placeholder="Search or create tags..."
        isValidNewOption={isValidNewOption}
        formatCreateLabel={(inputValue) => `Create tag "${normalizeAuditTagName(inputValue)}"...`}
        onCreateOption={(inputValue) => setPendingName(normalizeAuditTagName(inputValue))}
        noOptionsMessage={({ inputValue }) =>
          normalizeAuditTagName(inputValue).length > MAX_AUDIT_TAG_LENGTH
            ? `Tag names must be ${MAX_AUDIT_TAG_LENGTH} characters or fewer`
            : "No matching tags"
        }
        styles={selectStyles}
      />
      <ConfirmDialog
        open={pendingName !== null}
        onOpenChange={(open) => { if (!open) setPendingName(null); }}
        title="Create new tag?"
        description={<>Create the tag <strong>"{pendingName}"</strong>? Tags are shared, so everyone in your organization will be able to see and use it.</>}
        confirmLabel="Create Tag"
        onConfirm={createTag}
        loading={isCreating}
        onCloseAutoFocus={(e) => {
          // the dialog is opened from the select's menu, not a trigger button
          e.preventDefault();
          selectRef.current?.focus();
        }}
      />
    </>
  );
};
