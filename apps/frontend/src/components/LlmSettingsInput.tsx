import { useState, useEffect } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import { useGlobalStore } from "../utils";
import { StyledLabeledInput } from "./StyledLabeledInput";
import style from "./LlmSettingsInput.module.scss";

const apiClient = API.generateClient();

interface BedrockModel {
  modelId: string;
  modelName: string;
  providerName: string;
}

const DEFAULT_MODEL_ID = "amazon.nova-lite-v1:0";

export const LlmSettingsInput = () => {
  const queryClient = useQueryClient();
  const { setAnnounceMessage } = useGlobalStore();

  const [enabled, setEnabled] = useState(true);
  const [modelId, setModelId] = useState(DEFAULT_MODEL_ID);

  const { data: options } = useQuery({
    queryKey: ["llm-settings"],
    queryFn: async () => {
      const response = await apiClient.graphql({
        query: `query {
          options(where: { key: { _in: ["llm_enabled", "llm_model_id"] } }) {
            key
            value
          }
        }`,
      });
      const rows: { key: string; value: string }[] = (response as any)?.data?.options ?? [];
      return Object.fromEntries(rows.map(({ key, value }) => [key, value]));
    },
  });

  useEffect(() => {
    if (options) {
      setEnabled(options.llm_enabled !== "false");
      setModelId(options.llm_model_id || DEFAULT_MODEL_ID);
    }
  }, [options]);

  const { data: modelsData, isLoading: isLoadingModels } = useQuery({
    queryKey: ["bedrock-models"],
    queryFn: async () => {
      return (await (
        await API.get({
          apiName: "auth",
          path: "/getBedrockModels",
          options: {},
        }).response
      ).body.json()) as unknown as { models: BedrockModel[] };
    },
  });

  const models = modelsData?.models ?? [];

  const saveMutation = useMutation({
    mutationFn: async ({ nextEnabled, nextModelId }: { nextEnabled: boolean; nextModelId: string }) => {
      await apiClient.graphql({
        query: `mutation($objects: [options_insert_input!]!) {
          insert_options(
            objects: $objects,
            on_conflict: { constraint: options_pkey, update_columns: [value] }
          ) {
            affected_rows
          }
        }`,
        variables: {
          objects: [
            { key: "llm_enabled", value: String(nextEnabled) },
            { key: "llm_model_id", value: nextModelId },
          ],
        },
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["llm-settings"] });
      setAnnounceMessage("AI summary settings saved.", "success");
    },
    onError: () => {
      setAnnounceMessage("Failed to save AI summary settings.", "error");
    },
  });

  const handleToggle = (e: React.ChangeEvent<HTMLInputElement>) => {
    const next = e.target.checked;
    setEnabled(next);
    saveMutation.mutate({ nextEnabled: next, nextModelId: modelId });
  };

  const handleModelChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const next = e.target.value;
    setModelId(next);
    saveMutation.mutate({ nextEnabled: enabled, nextModelId: next });
  };

  return (
    <div className={style.LlmSettingsInput}>
      <h2>LLM Blocker Summaries</h2>
      <hr />

      <StyledLabeledInput className={style.toggleRow}>
        <label htmlFor="llm-enabled">Enable LLM summaries</label>
        <input
          id="llm-enabled"
          type="checkbox"
          checked={enabled}
          onChange={handleToggle}
          className={style.toggle}
        />
      </StyledLabeledInput>
      <p className={style.description}>
        When enabled, each blocker detail page automatically generates a plain-language
        explanation of the accessibility issue and step-by-step fix instructions using
        an AWS Bedrock LLM. Summaries are cached in the database and only re-generated
        on request. Disable this to stop all LLM calls site-wide.
      </p>

      <StyledLabeledInput>
        <label htmlFor="llm-model-id">Bedrock model</label>
        {isLoadingModels ? (
          <select id="llm-model-id" disabled>
            <option>Loading models…</option>
          </select>
        ) : (
          <select
            id="llm-model-id"
            value={modelId}
            onChange={handleModelChange}
            disabled={!enabled}
            className={style.modelSelect}
          >
            {models.length === 0 && (
              <option value={DEFAULT_MODEL_ID}>{DEFAULT_MODEL_ID} (default)</option>
            )}
            {models.map(m => (
              <option key={m.modelId} value={m.modelId}>
                {m.providerName} – {m.modelName} ({m.modelId})
              </option>
            ))}
          </select>
        )}
      </StyledLabeledInput>
      <p className={style.description}>
        Choose which AWS Bedrock foundation model generates the summaries. The list shows
        models available in your region that support on-demand inference and text output.
        Changing the model takes effect immediately for new or refreshed
        summaries; existing cached summaries are not regenerated automatically.
      </p>
    </div>
  );
};
